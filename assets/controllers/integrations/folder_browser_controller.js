import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["tree"];

    connect() {
        console.debug("✅ Stimulus Google Folder Browser Connected!");
        this.integrationId = this.element.dataset.folderBrowserIntegrationId;
        this.loaderIcon = "/images/loading.gif"; // ✅ Change this to your actual loader GIF path
        this.loadTree();
    }

    loadTree() {
        this.showLoader(this.treeTarget);

        fetch(`/backoffice/larp/integration/${this.integrationId}/folder/root`)
            .then(response => response.json())
            .then(data => {
                this.hideLoader(this.treeTarget);

                if (!data || Object.keys(data).length === 0) {
                    console.error("❌ Empty API response");
                    return;
                }

                this.treeTarget.innerHTML = ""; // Clear previous tree
                this.renderTree(data, this.treeTarget, 0);
            })
            .catch(error => {
                this.hideLoader(this.treeTarget);
                console.error("❌ Error initializing tree:", error);
            });
    }

    renderTree(data, parentElement, indentLevel) {
        const ul = document.createElement("ul");
        ul.classList.add("tree-list");

        Object.values(data).forEach(file => {
            const li = document.createElement("li");
            li.classList.add("tree-item");

            // Create a wrapper for alignment
            const row = document.createElement("div");
            row.classList.add("tree-row");
            row.style.paddingLeft = `${indentLevel * 20}px`;

            // Folder icon
            const iconSpan = document.createElement("span");
            iconSpan.innerHTML = this.getIcon(file.type, false);
            iconSpan.classList.add("tree-icon");

            // File/folder label
            const label = document.createElement("span");
            label.innerHTML = `${file.name} (${file.owner})`;
            label.classList.add("tree-label");
            label.dataset.folderId = file.id;

            if (file.type === "folder") {
                label.style.cursor = "pointer";
                label.addEventListener("dblclick", () => this.toggleSubfolders(file, li, iconSpan));
            }

            row.appendChild(iconSpan);
            row.appendChild(label);

            // Create checkboxes (Moved to the end)
            row.appendChild(this.createCheckbox(file.id, "view", "View"));
            row.appendChild(this.createCheckbox(file.id, "edit", "Edit"));

            li.appendChild(row);

            // Placeholder for subfolders
            const subfolderContainer = document.createElement("div");
            subfolderContainer.classList.add("subfolders");
            subfolderContainer.style.display = "none";
            li.appendChild(subfolderContainer);

            ul.appendChild(li);
        });

        parentElement.appendChild(ul);
    }

    createCheckbox(id, type, labelText) {
        const label = document.createElement("label");
        label.classList.add("checkbox-label");

        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.name = `permissions[${id}]`;
        checkbox.value = type;

        label.appendChild(checkbox);
        label.appendChild(document.createTextNode(` ${labelText}`));
        return label;
    }

    toggleSubfolders(file, element, iconSpan) {
        const subfolderContainer = element.querySelector(".subfolders");

        if (subfolderContainer.dataset.loaded === "true") {
            const isCollapsed = subfolderContainer.style.display === "none";
            subfolderContainer.style.display = isCollapsed ? "block" : "none";
            iconSpan.innerHTML = this.getIcon("folder", isCollapsed); // 🔥 Change folder icon
            return;
        }

        // 🔥 Change folder icon to loading spinner
        iconSpan.innerHTML = `<img src="${this.loaderIcon}" class="loading-icon" alt="Loading...">`;

        fetch(`/backoffice/larp/integration/${this.integrationId}/folder/${file.id}`)
            .then(response => response.json())
            .then(subfolders => {
                if (!subfolders || Object.keys(subfolders).length === 0) {
                    console.error("❌ No subfolders found");
                    return;
                }
                this.renderTree(subfolders, subfolderContainer, 1);
                subfolderContainer.dataset.loaded = "true";
                subfolderContainer.style.display = "block";
                iconSpan.innerHTML = this.getIcon("folder", true); // 🔥 Change to open folder
            })
            .catch(error => {
                console.error("❌ Error fetching subfolders:", error);
                iconSpan.innerHTML = this.getIcon("folder", false); // 🔥 Revert to closed folder on error
            });
    }

    getIcon(type, isOpen) {
        if (type === "folder") {
            return isOpen ? "📂" : "📁"; // 📁 = Closed, 📂 = Open
        }
        switch (type) {
            case "spreadsheet": return "📊";
            case "document": return "📝";
            default: return "📄";
        }
    }

    showLoader(target) {
        const loader = document.createElement("div");
        loader.classList.add("loader");
        loader.innerHTML = `<img src="${this.loaderIcon}" class="loading-icon" alt="Loading...">`;
        target.innerHTML = "";
        target.appendChild(loader);
    }

    hideLoader(target) {
        target.innerHTML = ""; // Remove loader
    }
}
