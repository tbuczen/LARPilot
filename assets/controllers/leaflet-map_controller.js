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
        locations: Array
    };

    connect() {
        console.log('Leaflet map viewer controller connected');
        console.log('Locations to display:', this.locationsValue);
        this.initMap();
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

            this.map = L.map('map', {
                crs: L.CRS.Simple,
                minZoom: -2,
                maxZoom: 2,
                center: [img.height / 2, img.width / 2],
                zoom: 0
            });

            // Add the image overlay
            L.imageOverlay(this.imageUrlValue, bounds).addTo(this.map);
            this.map.fitBounds(bounds);

            // Draw grid if visible
            if (this.gridVisibleValue) {
                this.drawGrid(img.width, img.height);
            }

            // Add location markers
            this.addLocationMarkers(img.width, img.height);
        };
    }

    drawGrid(width, height) {
        const rows = this.gridRowsValue;
        const cols = this.gridColumnsValue;
        const cellWidth = width / cols;
        const cellHeight = height / rows;

        const gridLayer = L.layerGroup().addTo(this.map);

        // Draw horizontal lines
        for (let i = 0; i <= rows; i++) {
            const y = i * cellHeight;
            L.polyline([[y, 0], [y, width]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(gridLayer);
        }

        // Draw vertical lines
        for (let i = 0; i <= cols; i++) {
            const x = i * cellWidth;
            L.polyline([[0, x], [height, x]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(gridLayer);
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
                        html: `<div style="font-size: 12px; color: rgba(0,0,0,${this.gridOpacityValue}); font-weight: bold;">${label}</div>`,
                        iconSize: [30, 30]
                    })
                }).addTo(gridLayer);
            }
        }
    }

    addLocationMarkers(width, height) {
        if (!this.locationsValue || this.locationsValue.length === 0) {
            console.log('No locations to display');
            return;
        }

        console.log(`Adding markers for ${this.locationsValue.length} locations`);
        const cellWidth = width / this.gridColumnsValue;
        const cellHeight = height / this.gridRowsValue;

        this.locationsValue.forEach(location => {
            if (!location.gridCoordinates || location.gridCoordinates.length === 0) {
                console.log(`Location ${location.name} has no grid coordinates`);
                return;
            }

            console.log(`Processing location: ${location.name} with coordinates:`, location.gridCoordinates);

            const markerColor = location.color || '#3388ff';

            // Highlight each grid cell for this location
            let totalX = 0, totalY = 0;
            location.gridCoordinates.forEach(coord => {
                const { row, col } = this.parseCellCoordinate(coord);

                // Calculate cell bounds
                const x1 = col * cellWidth;
                const y1 = row * cellHeight;
                const x2 = x1 + cellWidth;
                const y2 = y1 + cellHeight;

                // Draw highlighted rectangle for this cell
                L.rectangle([[y1, x1], [y2, x2]], {
                    color: markerColor,
                    fillColor: markerColor,
                    fillOpacity: 0.3,
                    weight: 2
                }).addTo(this.map);

                // Accumulate center coordinates
                totalX += col * cellWidth + cellWidth / 2;
                totalY += row * cellHeight + cellHeight / 2;
            });

            const centerX = totalX / location.gridCoordinates.length;
            const centerY = totalY / location.gridCoordinates.length;

            // Create marker with custom icon at the center
            const marker = L.marker([centerY, centerX], {
                icon: L.divIcon({
                    className: 'location-marker',
                    html: `<div style="background-color: ${markerColor}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>`,
                    iconSize: [20, 20]
                })
            }).addTo(this.map);

            // Add popup with location info
            let popupContent = `<strong>${location.name}</strong><br>`;
            if (location.type) {
                popupContent += `Type: ${location.type}<br>`;
            }
            if (location.capacity) {
                popupContent += `Capacity: ${location.capacity}<br>`;
            }
            popupContent += `Cells: ${location.gridCoordinates.join(', ')}`;

            marker.bindPopup(popupContent);
            console.log(`Added marker for location: ${location.name} at [${centerY}, ${centerX}]`);
        });
    }

    getCellLabel(row, col) {
        const letter = String.fromCharCode(65 + col); // A, B, C, ...
        return `${letter}${row + 1}`;
    }

    parseCellCoordinate(coord) {
        // Parse "A1" format to {row: 0, col: 0}
        const match = coord.match(/^([A-Z]+)(\d+)$/);
        if (!match) {
            return { row: 0, col: 0 };
        }

        const col = match[1].charCodeAt(0) - 65;
        const row = parseInt(match[2]) - 1;
        return { row, col };
    }
}
