import assert from 'assert/strict';
import { applyFactionGroupLayout } from '../../assets/utils/factionGroupLayout.js';

class FakeCollection extends Array {
    layout() {
        return { run: () => {} };
    }

    position(dim, val) {
        this.forEach(node => node.position(dim, val));
    }
}

class FakeNode {
    constructor(data) {
        this.data = data;
        this.pos = { x: 0, y: 0 };
        this.childrenNodes = [];
    }

    position(dim, val) {
        if (val === undefined) {
            if (dim === undefined) {
                return this.pos;
            }
            if (typeof dim === 'string') {
                return this.pos[dim];
            }
            if (dim.x !== undefined) { this.pos.x = dim.x; }
            if (dim.y !== undefined) { this.pos.y = dim.y; }
            return this.pos;
        }
        this.pos[dim] = val;
    }

    children(selector) {
        if (!selector) return new FakeCollection(...this.childrenNodes);
        const type = selector.match(/\[type="(.+)"\]/)[1];
        return new FakeCollection(...this.childrenNodes.filter(c => c.data.type === type));
    }
}

class FakeCy {
    constructor(elements) {
        this.nodesMap = new Map();
        elements.forEach(el => {
            const node = new FakeNode(el.data);
            if (el.position) {
                node.pos = { ...el.position };
            }
            this.nodesMap.set(el.data.id, node);
        });
        elements.forEach(el => {
            if (el.data.parent) {
                this.nodesMap.get(el.data.parent).childrenNodes.push(
                    this.nodesMap.get(el.data.id)
                );
            }
        });
    }

    nodes(selector) {
        if (selector) {
            const type = selector.match(/\[type="(.+)"\]/)[1];
            return Array.from(this.nodesMap.values()).filter(n => n.data.type === type);
        }
        return Array.from(this.nodesMap.values());
    }

    getElementById(id) {
        return this.nodesMap.get(id);
    }
}

const cy = new FakeCy([
    { data: { id: 'group1', type: 'factionGroup' }, position: { x: 100, y: 50 } },
    { data: { id: 'faction1', type: 'faction', parent: 'group1' }, position: { x: 120, y: 60 } },
    { data: { id: 'char1', type: 'character', parent: 'group1' } },
    { data: { id: 'group2', type: 'factionGroup' }, position: { x: 200, y: 100 } },
    { data: { id: 'faction2', type: 'faction', parent: 'group2' }, position: { x: 220, y: 110 } },
]);

applyFactionGroupLayout(cy);

const g1 = cy.getElementById('group1');
const g2 = cy.getElementById('group2');
assert.strictEqual(g1.position('y'), g2.position('y'));

const faction1 = cy.getElementById('faction1');
assert.strictEqual(faction1.position('x'), g1.position('x'));
assert.strictEqual(faction1.position('y'), g1.position('y'));

console.log('factionGroupLayout test passed');

