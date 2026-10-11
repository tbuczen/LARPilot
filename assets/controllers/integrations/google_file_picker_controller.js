import {Controller} from "@hotwired/stimulus";
import {loadGoogleApi} from "../../utils/googleApiLoader.js";

export default class extends Controller {
    static targets = ["selectedFiles", "selectedFilesInput","saveButton"];
    static values = {
        tokenUrl: String,
        tokenCsrf: String,
        reauthenticationMessage: String,
    };

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
                internalId: f.id,
                fileId: f.fileId,
                fileName: f.fileName,
                permission: f.permission || "reader",
                mimeType: f.mimeType,
            }));

            this.selectedFileObjects = this.initialFiles.map(f => ({
                internalId: f.internalId,
                id: f.fileId,
                name: f.fileName,
                permission: f.permission,
                mimeType: f.mimeType,
            }));
        } catch (e) {
            console.error("Failed to parse initial files JSON:", e);
        }
    }

    async openPicker(event) {
        event.preventDefault();

        const developerKey = this.element.dataset.googleKey;

        if (!this.pickerApiLoaded) {
            console.error("Google Picker API is still loading...");
            return;
        }
        if (!developerKey) {
            console.error("Missing developer key for Google Picker.");
            return;
        }

        const oauthToken = await this.fetchPickerToken();
        if (!oauthToken) {
            return;
        }

        this.loadPicker(oauthToken, developerKey);
    }

    async fetchPickerToken() {
        try {
            const response = await fetch(this.tokenUrlValue, {
                method: "POST",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "Accept": "application/json",
                    "X-CSRF-Token": this.tokenCsrfValue,
                },
            });

            if (response.status === 409) {
                window.alert(this.reauthenticationMessageValue);
                return null;
            }

            if (!response.ok) {
                console.error("Failed to obtain Google Picker token:", response.status);
                return null;
            }

            const payload = await response.json();
            return payload.accessToken || null;
        } catch (error) {
            console.error("Failed to obtain Google Picker token:", error);
            return null;
        }
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
        this.selectedFilesTarget.replaceChildren();

        if (this.selectedFileObjects.length === 0) {
            this.selectedFilesInputTarget.value = "";
            return;
        }

        const mappingsBaseUrl = this.element.dataset.mappingsBaseUrl;

        const table = document.createElement("table");
        table.classList.add("table", "table-striped");

        const headerRow = table.createTHead().insertRow();
        ["Name", "Permission", ""].forEach((label) => {
            const headerCell = document.createElement("th");
            headerCell.textContent = label;
            headerRow.appendChild(headerCell);
        });

        const tbody = table.createTBody();

        this.selectedFileObjects.forEach((fileObj, index) => {
            const row = tbody.insertRow();
            row.appendChild(this.buildNameCell(fileObj));
            row.appendChild(this.buildPermissionCell(fileObj, index));
            row.appendChild(this.buildActionsCell(fileObj, index, mappingsBaseUrl));
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
        this.selectedFilesInputTarget.value = JSON.stringify(finalArray);
    }

    buildNameCell(fileObj) {
        const cell = document.createElement("td");
        cell.append(`${this.getMimeIcon(fileObj.mimeType)}${fileObj.name ?? ""}`);
        cell.appendChild(document.createElement("br"));

        const fileIdLabel = document.createElement("small");
        fileIdLabel.textContent = fileObj.id ?? "";
        cell.appendChild(fileIdLabel);

        return cell;
    }

    buildPermissionCell(fileObj, index) {
        const cell = document.createElement("td");
        cell.appendChild(this.buildPermissionOption(fileObj, index, "reader", "Read"));
        cell.appendChild(this.buildPermissionOption(fileObj, index, "writer", "Edit"));

        return cell;
    }

    buildPermissionOption(fileObj, index, permission, label) {
        const wrapper = document.createElement("div");
        wrapper.classList.add("form-check", "form-check-inline");

        const input = document.createElement("input");
        input.classList.add("form-check-input");
        input.type = "radio";
        input.name = `perm-${index}`;
        input.value = permission;
        input.dataset.index = String(index);
        input.dataset.action = "change->google-file-picker#changePermission";
        input.checked = fileObj.permission === permission;

        const labelElement = document.createElement("label");
        labelElement.classList.add("form-check-label");
        labelElement.textContent = label;

        wrapper.append(input, labelElement);

        return wrapper;
    }

    buildActionsCell(fileObj, index, mappingsBaseUrl) {
        const cell = document.createElement("td");
        cell.classList.add("text-end");

        const group = document.createElement("div");
        group.classList.add("btn-group");

        const openLink = document.createElement("a");
        openLink.href = `https://drive.google.com/open?id=${encodeURIComponent(fileObj.id ?? "")}`;
        openLink.target = "_blank";
        openLink.rel = "noopener noreferrer";
        openLink.classList.add("btn", "btn-outline-info", "btn-sm");
        openLink.textContent = "Open";
        group.appendChild(openLink);

        if (fileObj.internalId) {
            const mappingLink = document.createElement("a");
            mappingLink.href = mappingsBaseUrl.replace("__FILE_ID__", encodeURIComponent(fileObj.internalId));
            mappingLink.classList.add("btn", "btn-outline-primary", "btn-sm");
            mappingLink.textContent = "Manage Mappings";
            group.appendChild(mappingLink);
        }

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.classList.add("btn", "btn-sm", "btn-outline-danger");
        removeButton.dataset.index = String(index);
        removeButton.dataset.action = "click->google-file-picker#removeFile";
        removeButton.textContent = "X";
        group.appendChild(removeButton);

        cell.appendChild(group);

        return cell;
    }

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
