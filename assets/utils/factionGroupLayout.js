export function applyFactionGroupLayout(cy) {
    const groups = cy.nodes('[type="factionGroup"]');
    if (groups.length === 0) {
        return;
    }

    // First pass: calculate all radii and find the maximum
    const groupData = [];
    let maxRadius = 0;
    
    groups.forEach((group, index) => {
        const characters = group.children('[type="character"]');
        const faction = group.children('[type="faction"]');
        const characterCount = characters.length;
        
        // Calculate flexible circle radius based on number of characters
        const minRadius = 50;
        const maxRadiusLimit = 300;
        
        // Better radius calculation: logarithmic growth to handle large numbers
        let circleRadius;
        if (characterCount <= 1) {
            circleRadius = minRadius;
        } else if (characterCount <= 5) {
            circleRadius = minRadius + (characterCount - 1) * 20; // 20px per character for small groups
        } else {
            // For larger groups, use logarithmic scaling
            circleRadius = minRadius + 80 + Math.log(characterCount - 4) * 30;
            circleRadius = Math.min(circleRadius, maxRadiusLimit);
        }
        
        maxRadius = Math.max(maxRadius, circleRadius);
        
        groupData.push({
            group,
            characters,
            faction,
            characterCount,
            circleRadius,
            index
        });
        
    });
    
    // Calculate spacing based on the largest radius to ensure no overlap
    const groupSpacing = Math.max(400, maxRadius * 2.8);

    // Second pass: position all groups
    groupData.forEach((data) => {
        const { group, characters, faction, circleRadius, index } = data;
        
        // Calculate group position (spread horizontally)
        const groupX = index * groupSpacing;
        const groupY = 0;
        
        // Position the faction group container
        group.position({ x: groupX, y: groupY });
        
        if (faction.length > 0) {
            // Position faction node at the center of the group
            faction.position({ x: groupX, y: groupY });
        }
        
        if (characters.length > 0) {
            // Create circle layout for characters around the faction
            const angleStep = (2 * Math.PI) / characters.length;
            
            characters.forEach((character, charIndex) => {
                const angle = charIndex * angleStep;
                const charX = groupX + Math.cos(angle) * circleRadius;
                const charY = groupY + Math.sin(angle) * circleRadius;
                
                character.position({ x: charX, y: charY });
            });
        }
    });
    
    cy.fit(groups, 50); // 50px padding
}