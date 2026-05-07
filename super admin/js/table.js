function filterTable(filterValue) {
    const table = document.querySelector('.admin-table tbody');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const deptCell = rows[i].getElementsByTagName('td')[3]; // 4th column is Department
        const statusCell = rows[i].getElementsByTagName('td')[4]; // 5th column is Status
        if (deptCell && statusCell) {
            const rowDept = (deptCell.innerText || deptCell.textContent).trim();
            const rowStatus = (statusCell.innerText || statusCell.textContent).trim();
            
            if (filterValue === 'All' || rowStatus === filterValue || rowDept === filterValue) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
    
    const filterBtn = document.getElementById('filterBtn');
    if (filterBtn) {
        if (filterValue === 'All') {
            filterBtn.innerHTML = '<i class="ph ph-funnel" style="font-size: 0.9rem;"></i> Filter';
            filterBtn.style.background = 'white';
            filterBtn.style.color = 'var(--secondary-purple, #5A3A82)';
        } else {
            filterBtn.innerHTML = '<i class="ph ph-funnel" style="font-size: 0.9rem;"></i> Filter: ' + filterValue;
            filterBtn.style.background = 'var(--secondary-purple, #5A3A82)';
            filterBtn.style.color = 'white';
        }
    }
    
    const dropdown = document.getElementById('filterDropdown');
    if (dropdown) dropdown.style.display = 'none';
}

function toggleFilterMenu() {
    const dropdown = document.getElementById('filterDropdown');
    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}

window.addEventListener('click', function(event) {
    if (!event.target.closest('#filterBtn') && !event.target.closest('#filterDropdown')) {
        const dropdown = document.getElementById('filterDropdown');
        if (dropdown && dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        }
    }
});

// Add hover effects via JS to keep it completely inline
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.filter-option').forEach(btn => {
        btn.addEventListener('mouseover', () => btn.style.backgroundColor = '#f8f9fa');
        btn.addEventListener('mouseout', () => btn.style.backgroundColor = 'white');
    });
});
