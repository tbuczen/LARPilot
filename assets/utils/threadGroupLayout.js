export function applyThreadGroupLayout(cy) {
    const groups = cy.nodes('[type="threadGroup"]');
    if (groups.length === 0) {
        return;
    }

    // First pass: calculate all dimensions and find the maximum
    const groupData = [];
    let maxWidth = 0;
    let maxHeight = 0;
    
    groups.forEach((group, index) => {
        const quests = group.children('[type="quest"]');
        const events = group.children('[type="event"]');
        const thread = group.children('[type="thread"]');
        const totalItems = quests.length + events.length;
        
        // Calculate grid dimensions for quests and events
        const itemsPerRow = Math.max(1, Math.ceil(Math.sqrt(totalItems)));
        const rows = Math.ceil(totalItems / itemsPerRow);
        
        // Calculate group dimensions
        const itemSpacing = 80;
        const groupWidth = Math.max(200, itemsPerRow * itemSpacing + 40);
        const groupHeight = Math.max(150, rows * itemSpacing + 80); // Extra space for thread node
        
        maxWidth = Math.max(maxWidth, groupWidth);
        maxHeight = Math.max(maxHeight, groupHeight);
        
        groupData.push({
            group,
            thread,
            quests,
            events,
            totalItems,
            itemsPerRow,
            rows,
            groupWidth,
            groupHeight,
            index
        });
        
    });
    
    // Calculate spacing between groups
    const groupSpacing = Math.max(300, maxWidth + 50);

    // Second pass: position all groups
    groupData.forEach((data) => {
        const { group, thread, quests, events, itemsPerRow, groupWidth, groupHeight, index } = data;
        
        // Calculate group position (spread horizontally)
        const groupX = index * groupSpacing;
        const groupY = 0;
        
        // Position the thread group container
        group.position({ x: groupX, y: groupY });
        
        // Position thread node at top-left of the group
        if (thread.length > 0) {
            const threadX = groupX - groupWidth/2 + 60; // Offset from left edge
            const threadY = groupY - groupHeight/2 + 40; // Offset from top edge
            thread.position({ x: threadX, y: threadY });
        }
        
        // Combine quests and events for grid layout
        const allItems = [...quests, ...events];
        
        if (allItems.length > 0) {
            // Create grid layout for quests and events
            const itemSpacing = 80;
            const startX = groupX - (itemsPerRow - 1) * itemSpacing / 2;
            const startY = groupY - 20; // Slightly below center to leave space for thread
            
            allItems.forEach((item, itemIndex) => {
                const row = Math.floor(itemIndex / itemsPerRow);
                const col = itemIndex % itemsPerRow;
                
                const itemX = startX + col * itemSpacing;
                const itemY = startY + row * itemSpacing;
                
                item.position({ x: itemX, y: itemY });
            });
        }
    });
    
    cy.fit(groups, 50); // 50px padding
}
