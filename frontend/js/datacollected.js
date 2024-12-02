// Toggle the visibility of Types of Data Collected dropdown
document.getElementById('data-types-box').addEventListener('click', function() {
    var dropdown = document.getElementById('data-types-list');
    dropdown.classList.toggle('show');
});

// Prevent dropdown from closing when clicking inside (on the checkboxes)
document.getElementById('data-types-list').addEventListener('click', function(event) {
    event.stopPropagation(); // Prevent click inside the dropdown from closing it
});

// Update the dropdown box text based on selected checkboxes
document.querySelectorAll('#data-types-list input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        updateSelectedDataTypes();
    });
});

// Close the dropdown if the user clicks outside
window.onclick = function(event) {
    if (!event.target.matches('.multiselect-dropdown-box2')) {
        var dropdowns = document.getElementsByClassName('multiselect-dropdown-list2');
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
};

// Function to update the dropdown box text with selected checkboxes
function updateSelectedDataTypes() {
    var selectedOptions = [];
    document.querySelectorAll('#data-types-list input[type="checkbox"]:checked').forEach(function(checkbox) {
        selectedOptions.push(checkbox.value);
    });
    
    var dropdownBox = document.getElementById('data-types-box');
    if (selectedOptions.length > 0) {
        dropdownBox.textContent = selectedOptions.join(', ');
    } else {
        dropdownBox.textContent = 'Select Types of Data Collected';
    }
}