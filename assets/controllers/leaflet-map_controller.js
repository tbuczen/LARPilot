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
        locations: Array,
        staffPositions: Array
    };

    connect() {
        this.staffPositionsLayer = null;
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
            this.imageWidth = img.width;
            this.imageHeight = img.height;
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

            // Add staff position markers if present
            if (this.hasStaffPositionsValue && this.staffPositionsValue.length > 0) {
                this.addStaffPositionMarkers();
            }
        };
    }

    toggleStaffPositions(event) {
        if (event.target.checked) {
            this.addStaffPositionMarkers();
        } else if (this.staffPositionsLayer) {
            this.staffPositionsLayer.remove();
            this.staffPositionsLayer = null;
        }
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
            return;
        }

        this.locationsValue.forEach(location => {
            const markerColor = location.color || '#3388ff';
            const shape = location.shape || 'dot';

            // Convert percentage position to pixel coordinates
            const x = (location.positionX / 100) * width;
            const y = (location.positionY / 100) * height;

            // Create marker with shaped icon
            const marker = L.marker([y, x], {
                icon: this.createShapedIcon(shape, markerColor)
            }).addTo(this.map);

            // Add popup with location info
            let popupContent = `<strong>${location.name}</strong>`;
            if (location.type) {
                popupContent += `<br>Type: ${location.type}`;
            }
            if (location.capacity) {
                popupContent += `<br>Capacity: ${location.capacity}`;
            }
            if (location.description) {
                popupContent += `<br><small>${location.description}</small>`;
            }

            marker.bindPopup(popupContent);
        });
    }

    createShapedIcon(shape, color) {
        const svgContent = this.getSvgForShape(shape, color);

        return L.divIcon({
            className: 'location-marker-icon',
            html: `<div style="
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            ">${svgContent}</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });
    }

    getSvgForShape(shape, color) {
        const shapes = {
            dot: `<circle cx="12" cy="12" r="8" fill="${color}" stroke="white" stroke-width="2"/>`,
            circle: `<circle cx="12" cy="12" r="10" fill="${color}" fill-opacity="0.3" stroke="${color}" stroke-width="3"/>`,
            square: `<rect x="2" y="2" width="20" height="20" fill="${color}" stroke="white" stroke-width="2"/>`,
            diamond: `<polygon points="12,2 22,12 12,22 2,12" fill="${color}" stroke="white" stroke-width="2"/>`,
            triangle: `<polygon points="12,2 22,22 2,22" fill="${color}" stroke="white" stroke-width="2"/>`,
            house: `<path d="M12,2L2,10v12h8v-6h4v6h8V10z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_up: `<path d="M12,2L22,14H16v8H8v-8H2z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_down: `<path d="M12,22L2,10H8V2h8v8h6z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_left: `<path d="M2,12L14,2v6h8v8h-8v6z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_right: `<path d="M22,12L10,22v-6H2V8h8V2z" fill="${color}" stroke="white" stroke-width="2"/>`,
            star: `<polygon points="12,2 15,8.5 22,9.5 17,15 18.2,22 12,18 5.8,22 7,15 2,9.5 9,8.5" fill="${color}" stroke="white" stroke-width="2"/>`,
            flag: `<path d="M4,2v20h2v-8h12l-4-6l4-6z" fill="${color}" stroke="white" stroke-width="2"/>`,
            pin: `<path d="M12,2C8,2 5,5 5,9c0,5 7,13 7,13s7-8 7-13c0-4-3-7-7-7z" fill="${color}" stroke="white" stroke-width="2"/><circle cx="12" cy="9" r="3" fill="white"/>`,
            cross: `<path d="M4,8h6V2h4v6h6v4h-6v6h-4v-6H4z" fill="${color}" stroke="white" stroke-width="2"/>`
        };

        const shapeSvg = shapes[shape] || shapes.dot;
        return `<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">${shapeSvg}</svg>`;
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

    addStaffPositionMarkers() {
        if (!this.staffPositionsValue || this.staffPositionsValue.length === 0) {
            return;
        }

        // Remove existing layer if any
        if (this.staffPositionsLayer) {
            this.staffPositionsLayer.remove();
        }

        this.staffPositionsLayer = L.layerGroup().addTo(this.map);

        const cellWidth = this.imageWidth / this.gridColumnsValue;
        const cellHeight = this.imageHeight / this.gridRowsValue;

        this.staffPositionsValue.forEach(position => {
            const { row, col } = this.parseCellCoordinate(position.gridCell);
            const centerX = col * cellWidth + cellWidth / 2;
            const centerY = row * cellHeight + cellHeight / 2;

            // Determine marker color based on role
            const color = this.getRoleColor(position.roles);

            // Create marker
            const marker = L.marker([centerY, centerX], {
                icon: L.divIcon({
                    className: 'staff-position-marker',
                    html: `
                        <div style="
                            background-color: ${color};
                            width: 28px;
                            height: 28px;
                            border-radius: 50%;
                            border: 2px solid white;
                            box-shadow: 0 0 6px rgba(0,0,0,0.5);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <i class="bi bi-person-fill" style="color: white; font-size: 14px;"></i>
                        </div>
                    `,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                })
            }).addTo(this.staffPositionsLayer);

            // Create popup content
            let popupContent = `
                <div style="min-width: 150px;">
                    <strong>${position.participantName}</strong><br>
                    <small class="text-muted">
                        ${position.roles.map(r => r.replace('ROLE_', '').toLowerCase()).join(', ')}
                    </small><br>
                    <strong>${position.gridCell}</strong>
                    ${position.statusNote ? `<br><em>${position.statusNote}</em>` : ''}
                    <br><small class="text-muted">Updated: ${position.updatedAt}</small>
                </div>
            `;

            marker.bindPopup(popupContent);
        });
    }

    getRoleColor(roles) {
        // Color coding by role priority
        if (roles.includes('ROLE_ORGANIZER')) {
            return '#dc3545'; // Red - main organizer
        }
        if (roles.includes('ROLE_PERSON_OF_TRUST')) {
            return '#28a745'; // Green - trust person
        }
        if (roles.includes('ROLE_PHOTOGRAPHER')) {
            return '#17a2b8'; // Cyan - photographer
        }
        if (roles.includes('ROLE_MEDIC')) {
            return '#ffc107'; // Yellow - medic
        }
        if (roles.includes('ROLE_GAME_MASTER')) {
            return '#6f42c1'; // Purple - game master
        }
        if (roles.includes('ROLE_STAFF')) {
            return '#fd7e14'; // Orange - staff
        }
        return '#6c757d'; // Gray - other
    }
}
