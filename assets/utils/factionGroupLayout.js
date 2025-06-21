export function applyFactionGroupLayout(cy) {
    const groups = cy.nodes('[type="factionGroup"]');
    if (groups.length === 0) {
        return;
    }

    groups.forEach((group) => {
        const characters = group.children('[type="character"]');
        if (characters.length) {
            characters.layout({
                name: 'circle',
                padding: 5,
            }).run();
        }
    });

    const baseY = groups[0].position('y');

    groups.forEach((group) => {
        group.position('y', baseY);
        const faction = group.children('[type="faction"]');
        if (faction.length) {
            faction.position(group.position());
        }
    });
}
