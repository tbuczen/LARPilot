import {Controller} from "@hotwired/stimulus";
import {loadGoogleApi} from "../../utils/googleApiLoader.js";

export default class extends Controller {
    static targets = ["selectedFiles", "selectedFilesInput","saveButton"];

    connect() {
        this.pickerApiLoaded = false;
        this.selectedFileObjects = [];
        this.initialFiles = [];

        this.loadInitialFiles();
        this.renderFileList();

        loadGoogleApi()
            .then(() => {
                this.pickerApiLoaded = true;
            })
            .catch((error) => {
                console.error(error);
            });
    }

    loadInitialFiles() {
        let rawInitial = this.element.dataset.initialFiles || "[]";
        try {
            let parsed = JSON.parse(rawInitial);
            if (!Array.isArray(parsed)) {
                parsed = [];
            }

            this.initialFiles = parsed.map(f => ({
                fileId: f.fileId,
                fileName: f.fileName,
                permission: f.permission || "reader",
                mimeType: f.mimeType,
            }));

            this.selectedFileObjects = this.initialFiles.map(f => ({
                id: f.fileId,
                name: f.fileName,
                permission: f.permission,
                mimeType: f.mimeType,
            }));
        } catch (e) {
            console.error("Failed to parse initial files JSON:", e);
        }
    }

    openPicker(event) {
        event.preventDefault();

        const oauthToken = this.element.dataset.oauthToken;
        const developerKey = this.element.dataset.googleKey;

        if (!this.pickerApiLoaded) {
            console.error("Google Picker API is still loading...");
            return;
        }
        if (!oauthToken) {
            console.error("Missing OAuth token for Google Picker.");
            return;
        }
        if (!developerKey) {
            console.error("Missing developer key for Google Picker.");
            return;
        }

        this.loadPicker(oauthToken, developerKey);
    }

    loadPicker(oauthToken, developerKey) {
        const docsView = new google.picker.DocsView()
            .setIncludeFolders(true)
            .setSelectFolderEnabled(true)
            .setMimeTypes(
                "application/vnd.google-apps.document," +
                "application/vnd.google-apps.spreadsheet"
            );

        const picker = new google.picker.PickerBuilder()
            .setOAuthToken(oauthToken)
            .setDeveloperKey(developerKey)
            .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
            .addView(docsView)
            .setCallback(this.onFilePicked.bind(this))
            .build();

        picker.setVisible(true);
    }

    onFilePicked(data) {
        if (data.action !== google.picker.Action.PICKED) {
            return;
        }

        data.docs.forEach((doc) => {
            if (!this.selectedFileObjects.some((f) => f.id === doc.id)) {
                this.selectedFileObjects.push({
                    id: doc.id,
                    name: doc.name,
                    permission: "reader",
                    mimeType: doc.mimeType,
                });
            }
        });

        this.renderFileList();
    }

    renderFileList() {
        this.selectedFilesTarget.innerHTML = "";

        if (this.selectedFileObjects.length === 0) {
            this.selectedFilesInputTarget.value = "";
            return;
        }

        const table = document.createElement("table");
        table.classList.add("table", "table-striped");

        table.innerHTML = `
      <thead>
        <tr>
          <th>Name</th>
          <th>Permission</th>
          <th></th>
        </tr>
      </thead>
      <tbody></tbody>
    `;

        const tbody = table.querySelector("tbody");

        this.selectedFileObjects.forEach((fileObj, index) => {
            // Build a row
            const row = document.createElement("tr");

            row.innerHTML = `
        <td>
          ${this.getMimeIcon(fileObj.mimeType)}
          ${fileObj.name}
          <br><small>${fileObj.id}</small>
        </td>
        <td>
          <div class="form-check form-check-inline">
            <input 
              class="form-check-input" 
              type="radio" 
              name="perm-${index}" 
              value="reader" 
              data-index="${index}" 
              data-action="change->google-file-picker#changePermission" 
              ${fileObj.permission === "reader" ? "checked" : ""}
            >
            <label class="form-check-label">Read</label>
          </div>
          <div class="form-check form-check-inline">
            <input 
              class="form-check-input" 
              type="radio" 
              name="perm-${index}" 
              value="writer" 
              data-index="${index}" 
              data-action="change->google-file-picker#changePermission" 
              ${fileObj.permission === "writer" ? "checked" : ""}
            >
            <label class="form-check-label">Edit</label>
          </div>
        </td>
        <td>
          <button 
            type="button" 
            class="btn btn-sm btn-danger"
            data-index="${index}"
            data-action="click->google-file-picker#removeFile"
          >
            X
          </button>
        </td>
      `;

            tbody.appendChild(row);
        });

        this.selectedFilesTarget.appendChild(table);

        const finalArray = this.selectedFileObjects.map((obj) => {
            return {
                fileId: obj.id,
                fileName: obj.name,
                permission: obj.permission,
                mimeType: obj.mimeType,
            };
        });

        const changed = JSON.stringify(finalArray) !== JSON.stringify(this.initialFiles);

        this.saveButtonTarget.disabled = !changed;
        this.selectedFilesInputTarget.value = JSON.stringify(finalArray);    }

    changePermission(event) {
        const index = parseInt(event.target.dataset.index, 10);
        this.selectedFileObjects[index].permission = event.target.value;
        this.renderFileList();
    }

    removeFile(event) {
        const index = parseInt(event.target.dataset.index, 10);
        this.selectedFileObjects.splice(index, 1);
        this.renderFileList();
    }

    getMimeIcon(mimeType) {
        if (mimeType === 'application/vnd.google-apps.folder') {
            return '📁 ';
        }
        if (mimeType === 'application/vnd.google-apps.document') {
            return '📄 ';
        }
        if (mimeType === 'application/vnd.google-apps.spreadsheet') {
            return '📊 ';
        }
        return '📎 ';
    }
}
