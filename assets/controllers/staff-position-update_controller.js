import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

/**
 * Stimulus controller for updating staff position on a game map.
 * Provides tap-to-select interface for mobile-friendly grid selection.
 */
export default class extends Controller {
    static values = {
        apiUrl: String,
        removeUrl: String,
        imageUrl: String,
        gridRows: Number,
        gridColumns: Number,
        gridOpacity: { type: Number, default: 0.5 },
        currentCell: String
    };

    static targets = ['mapContainer', 'cellDisplay', 'statusNote', 'saveButton', 'status'];

    connect() {
        this.selectedCell = this.currentCellValue || null;
        this.highlightLayer = null;
        this.initMap();
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
        }
    }

    initMap() {
        if (!this.hasMapContainerTarget) {
            console.error('Map container target not found');
            return;
        }

        const img = new Image();
        img.src = this.imageUrlValue;

        img.onload = () => {
            this.imageWidth = img.width;
            this.imageHeight = img.height;
            const bounds = [[0, 0], [img.height, img.width]];

            this.map = L.map(this.mapContainerTarget, {
                crs: L.CRS.Simple,
                minZoom: -2,
                maxZoom: 2,
                center: [img.height / 2, img.width / 2],
                zoom: 0,
                zoomControl: true,
                attributionControl: false
            });

            // Add the image overlay
            L.imageOverlay(this.imageUrlValue, bounds).addTo(this.map);
            this.map.fitBounds(bounds);

            // Draw grid
            this.drawGrid();

            // Highlight current cell if exists
            if (this.selectedCell) {
                this.highlightCell(this.selectedCell);
            }

            // Add click handler for cell selection
            this.map.on('click', (e) => this.onMapClick(e));
        };
    }

    drawGrid() {
        const rows = this.gridRowsValue;
        const cols = this.gridColumnsValue;
        const cellWidth = this.imageWidth / cols;
        const cellHeight = this.imageHeight / rows;

        this.gridLayer = L.layerGroup().addTo(this.map);

        // Draw horizontal lines
        for (let i = 0; i <= rows; i++) {
            const y = i * cellHeight;
            L.polyline([[y, 0], [y, this.imageWidth]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(this.gridLayer);
        }

        // Draw vertical lines
        for (let i = 0; i <= cols; i++) {
            const x = i * cellWidth;
            L.polyline([[0, x], [this.imageHeight, x]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(this.gridLayer);
        }

        // Add grid labels
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const x = col * cellWidth + cellWidth / 2;
                const y = row * cellHeight + cellHeight / 2;
                const label = this.getCellLabel(row, col);

                L.marker([y, x], {
                    icon: L.divIcon({
                        className: 'grid-label',
                        html: `<div style="font-size: 10px; color: rgba(0,0,0,${this.gridOpacityValue}); font-weight: bold; pointer-events: none;">${label}</div>`,
                        iconSize: [25, 15]
                    }),
                    interactive: false
                }).addTo(this.gridLayer);
            }
        }
    }

    onMapClick(e) {
        const cellWidth = this.imageWidth / this.gridColumnsValue;
        const cellHeight = this.imageHeight / this.gridRowsValue;

        // Convert click coordinates to grid cell
        const col = Math.floor(e.latlng.lng / cellWidth);
        const row = Math.floor(e.latlng.lat / cellHeight);

        // Validate bounds
        if (col < 0 || col >= this.gridColumnsValue || row < 0 || row >= this.gridRowsValue) {
            return;
        }

        const cellLabel = this.getCellLabel(row, col);
        this.selectCell(cellLabel, row, col);
    }

    selectCell(cellLabel, row, col) {
        this.selectedCell = cellLabel;

        // Update cell display
        if (this.hasCellDisplayTarget) {
            this.cellDisplayTarget.value = cellLabel;
        }

        // Enable save button
        if (this.hasSaveButtonTarget) {
            this.saveButtonTarget.disabled = false;
        }

        // Highlight the selected cell
        this.highlightCell(cellLabel);
    }

    highlightCell(cellLabel) {
        // Remove previous highlight
        if (this.highlightLayer) {
            this.highlightLayer.remove();
        }

        const { row, col } = this.parseCellLabel(cellLabel);
        const cellWidth = this.imageWidth / this.gridColumnsValue;
        const cellHeight = this.imageHeight / this.gridRowsValue;

        const x1 = col * cellWidth;
        const y1 = row * cellHeight;
        const x2 = x1 + cellWidth;
        const y2 = y1 + cellHeight;

        // Create highlight rectangle
        this.highlightLayer = L.rectangle([[y1, x1], [y2, x2]], {
            color: '#007bff',
            fillColor: '#007bff',
            fillOpacity: 0.4,
            weight: 3
        }).addTo(this.map);

        // Add marker at center
        const centerX = (x1 + x2) / 2;
        const centerY = (y1 + y2) / 2;

        L.marker([centerY, centerX], {
            icon: L.divIcon({
                className: 'selected-marker',
                html: `<div style="background-color: #007bff; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;"><i class="bi bi-person-fill" style="color: white; font-size: 16px;"></i></div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(this.highlightLayer);
    }

    getCellLabel(row, col) {
        const letter = String.fromCharCode(65 + col); // A, B, C, ...
        return `${letter}${row + 1}`;
    }

    parseCellLabel(label) {
        const match = label.match(/^([A-Z]+)(\d+)$/);
        if (!match) {
            return { row: 0, col: 0 };
        }

        const col = match[1].charCodeAt(0) - 65;
        const row = parseInt(match[2]) - 1;
        return { row, col };
    }

    async save() {
        if (!this.selectedCell) {
            if (this.hasStatusTarget) {
                this.statusTarget.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-1"></i> Please select a cell first';
            }
            return;
        }

        const statusNote = this.hasStatusNoteTarget ? this.statusNoteTarget.value : '';

        // Disable save button
        if (this.hasSaveButtonTarget) {
            this.saveButtonTarget.disabled = true;
            this.saveButtonTarget.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
        }

        try {
            const response = await fetch(this.apiUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    gridCell: this.selectedCell,
                    statusNote: statusNote
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Update status display
            if (this.hasStatusTarget) {
                const now = new Date();
                const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                this.statusTarget.innerHTML = `<i class="bi bi-check-circle text-success me-1"></i> Updated: ${timeString}`;
            }

            // Show success feedback
            if (this.hasSaveButtonTarget) {
                this.saveButtonTarget.innerHTML = '<i class="bi bi-check-circle me-1"></i> Saved!';
                this.saveButtonTarget.classList.remove('btn-primary');
                this.saveButtonTarget.classList.add('btn-success');

                setTimeout(() => {
                    this.saveButtonTarget.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Position';
                    this.saveButtonTarget.classList.remove('btn-success');
                    this.saveButtonTarget.classList.add('btn-primary');
                    this.saveButtonTarget.disabled = false;
                }, 2000);
            }

        } catch (error) {
            console.error('Error saving position:', error);

            if (this.hasStatusTarget) {
                this.statusTarget.innerHTML = `<i class="bi bi-exclamation-circle text-danger me-1"></i> Error saving position`;
            }

            if (this.hasSaveButtonTarget) {
                this.saveButtonTarget.innerHTML = '<i class="bi bi-x-circle me-1"></i> Error';
                this.saveButtonTarget.classList.remove('btn-primary');
                this.saveButtonTarget.classList.add('btn-danger');

                setTimeout(() => {
                    this.saveButtonTarget.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Position';
                    this.saveButtonTarget.classList.remove('btn-danger');
                    this.saveButtonTarget.classList.add('btn-primary');
                    this.saveButtonTarget.disabled = false;
                }, 2000);
            }
        }
    }

    async remove() {
        if (!this.hasRemoveUrlValue) {
            console.error('Remove URL not configured');
            return;
        }

        if (!confirm('Are you sure you want to remove your position?')) {
            return;
        }

        try {
            const response = await fetch(this.removeUrlValue, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Clear selection
            this.selectedCell = null;

            if (this.highlightLayer) {
                this.highlightLayer.remove();
                this.highlightLayer = null;
            }

            if (this.hasCellDisplayTarget) {
                this.cellDisplayTarget.value = '';
            }

            if (this.hasStatusNoteTarget) {
                this.statusNoteTarget.value = '';
            }

            if (this.hasStatusTarget) {
                this.statusTarget.innerHTML = '<i class="bi bi-info-circle me-1"></i> Position removed';
            }

            // Reload the page to update UI (remove the remove button)
            window.location.reload();

        } catch (error) {
            console.error('Error removing position:', error);

            if (this.hasStatusTarget) {
                this.statusTarget.innerHTML = `<i class="bi bi-exclamation-circle text-danger me-1"></i> Error removing position`;
            }
        }
    }
}
