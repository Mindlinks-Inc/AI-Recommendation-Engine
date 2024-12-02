// File: industry-regulations-dropdown.js

document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.getElementById('industry-regulations-box');
    const list = document.getElementById('industry-regulations-list');
    const checkboxes = list.querySelectorAll('input[type="checkbox"]');

    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        list.style.display = list.style.display === 'block' ? 'none' : 'block';
        this.classList.toggle('active');
    });

    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateSelected);
    });

    function updateSelected() {
        const selected = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        if (selected.length > 0) {
            dropdown.textContent = selected.join(', ');
        } else {
            dropdown.textContent = 'Select Industry Regulations';
        }
    }

    // Close the dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!dropdown.contains(event.target) && !list.contains(event.target)) {
            list.style.display = 'none';
            dropdown.classList.remove('active');
        }
    });

    // Prevent closing when clicking inside the list
    list.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});