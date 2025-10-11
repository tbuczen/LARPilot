import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

export default class extends Controller {
    static values = {
        imageUrl: String,
        gridRows: Number,
        gridColumns: Number,
        gridOpacity: Number,
        gridVisible: Boolean,
        coordinatesTarget: String
    };

    connect() {
        this.selectedCells = new Set();
        this.cellLayers = {};
        this.initMap();
        this.loadExistingCoordinates();
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
        }
    }

    initMap() {
        const img = new Image();
        img.src = this.imageUrlValue;

        img.onload = () => {
            const bounds = [[0, 0], [img.height, img.width]];

            this.map = L.map('map-editor', {
                crs: L.CRS.Simple,
                minZoom: -2,
                maxZoom: 2,
                center: [img.height / 2, img.width / 2],
                zoom: 0
            });

            // Add the image overlay
            L.imageOverlay(this.imageUrlValue, bounds).addTo(this.map);
            this.map.fitBounds(bounds);

            this.cellWidth = img.width / this.gridColumnsValue;
            this.cellHeight = img.height / this.gridRowsValue;

            // Draw grid with clickable cells
            this.drawInteractiveGrid(img.width, img.height);
        };
    }

    drawInteractiveGrid(width, height) {
        const rows = this.gridRowsValue;
        const cols = this.gridColumnsValue;

        // Draw grid lines
        const gridLayer = L.layerGroup().addTo(this.map);

        for (let i = 0; i <= rows; i++) {
            const y = i * this.cellHeight;
            L.polyline([[y, 0], [y, width]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(gridLayer);
        }

        for (let i = 0; i <= cols; i++) {
            const x = i * this.cellWidth;
            L.polyline([[0, x], [height, x]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(gridLayer);
        }

        // Create clickable cells
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const x1 = col * this.cellWidth;
                const y1 = row * this.cellHeight;
                const x2 = x1 + this.cellWidth;
                const y2 = y1 + this.cellHeight;

                const cellBounds = [[y1, x1], [y2, x2]];
                const cellLabel = this.getCellLabel(row, col);

                const cell = L.rectangle(cellBounds, {
                    color: 'transparent',
                    fillColor: '#3388ff',
                    fillOpacity: 0,
                    weight: 0
                }).addTo(this.map);

                cell.on('click', () => this.toggleCell(row, col, cell));

                // Add label
                const centerX = x1 + this.cellWidth / 2;
                const centerY = y1 + this.cellHeight / 2;

                L.marker([centerY, centerX], {
                    icon: L.divIcon({
                        className: 'grid-label',
                        html: `<div style="font-size: 12px; color: rgba(0,0,0,${this.gridOpacityValue}); font-weight: bold; pointer-events: none;">${cellLabel}</div>`,
                        iconSize: [30, 30]
                    })
                }).addTo(gridLayer);

                this.cellLayers[cellLabel] = cell;
            }
        }
    }

    toggleCell(row, col, cellLayer) {
        const cellLabel = this.getCellLabel(row, col);

        if (this.selectedCells.has(cellLabel)) {
            this.selectedCells.delete(cellLabel);
            cellLayer.setStyle({ fillOpacity: 0 });
        } else {
            this.selectedCells.add(cellLabel);
            cellLayer.setStyle({ fillOpacity: 0.3 });
        }

        this.updateFormField();
    }

    updateFormField() {
        const targetId = this.coordinatesTargetValue;
        const input = document.getElementById(targetId);

        if (input) {
            const coordinates = Array.from(this.selectedCells).sort();
            input.value = JSON.stringify(coordinates);
        }
    }

    loadExistingCoordinates() {
        const targetId = this.coordinatesTargetValue;
        const input = document.getElementById(targetId);

        if (input && input.value) {
            try {
                const coordinates = JSON.parse(input.value);
                if (Array.isArray(coordinates)) {
                    coordinates.forEach(coord => {
                        this.selectedCells.add(coord);
                        if (this.cellLayers[coord]) {
                            this.cellLayers[coord].setStyle({ fillOpacity: 0.3 });
                        }
                    });
                }
            } catch (e) {
                console.error('Failed to parse existing coordinates:', e);
            }
        }
    }

    getCellLabel(row, col) {
        const letter = String.fromCharCode(65 + col); // A, B, C, ...
        return `${letter}${row + 1}`;
    }
}
