import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'checkbox',
        'selectAll',
        'bulkButton',
        'counter',
        'linkModal',
        'linkItems',
        'linkLabel',
        'createModal',
        'createItem',
        'createType',
        'createLabel',
    ];

    static values = {
        selectedLabel: String,
    };

    connect() {
        this.boundPrepareLink = this.prepareLink.bind(this);
        this.boundPrepareCreate = this.prepareCreate.bind(this);
        if (this.hasLinkModalTarget) {
            this.linkModalTarget.addEventListener('show.bs.modal', this.boundPrepareLink);
        }
        if (this.hasCreateModalTarget) {
            this.createModalTarget.addEventListener('show.bs.modal', this.boundPrepareCreate);
        }
        this.updateSelection();
    }

    disconnect() {
        if (this.hasLinkModalTarget) {
            this.linkModalTarget.removeEventListener('show.bs.modal', this.boundPrepareLink);
        }
        if (this.hasCreateModalTarget) {
            this.createModalTarget.removeEventListener('show.bs.modal', this.boundPrepareCreate);
        }
    }

    toggleAll() {
        const checked = this.selectAllTarget.checked;
        this.checkboxTargets.forEach((checkbox) => {
            checkbox.checked = checked;
        });
        this.updateSelection();
    }

    updateSelection() {
        const selectedCount = this.selectedIds().length;
        this.bulkButtonTargets.forEach((button) => {
            button.disabled = selectedCount === 0;
        });
        if (this.hasCounterTarget) {
            this.counterTarget.textContent = selectedCount > 0 ? this.selectedLabel(selectedCount) : '';
        }
        if (this.hasSelectAllTarget) {
            const total = this.checkboxTargets.length;
            this.selectAllTarget.checked = total > 0 && selectedCount === total;
            this.selectAllTarget.indeterminate = selectedCount > 0 && selectedCount < total;
        }
    }

    prepareLink(event) {
        const trigger = event.relatedTarget;
        const singleItemId = trigger?.dataset?.itemId;
        const itemIds = singleItemId ? [singleItemId] : this.selectedIds();

        const inputs = itemIds.map((itemId) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[]';
            input.value = itemId;
            return input;
        });
        this.linkItemsTarget.replaceChildren(...inputs);

        if (this.hasLinkLabelTarget) {
            this.linkLabelTarget.textContent = singleItemId ? (trigger.dataset.itemTitle || '') : this.selectedLabel(itemIds.length);
        }
    }

    prepareCreate(event) {
        const trigger = event.relatedTarget;
        if (!trigger?.dataset?.itemId) {
            return;
        }
        this.createItemTarget.value = trigger.dataset.itemId;
        this.createLabelTarget.textContent = trigger.dataset.itemTitle || '';
        const targetType = trigger.dataset.targetType || '';
        const hasOption = Array.from(this.createTypeTarget.options).some((option) => option.value === targetType);
        this.createTypeTarget.value = hasOption ? targetType : '';
    }

    selectedIds() {
        return this.checkboxTargets.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
    }

    selectedLabel(count) {
        return this.selectedLabelValue.replace('%count%', String(count));
    }
}
