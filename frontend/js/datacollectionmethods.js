// Toggle the visibility of Data Collection Methods dropdown
document.getElementById('data-collection-box').addEventListener('click', function() {
    var dropdown = document.getElementById('data-collection-list');
    dropdown.classList.toggle('show');
});

// Prevent dropdown from closing when clicking inside (on the checkboxes)
document.getElementById('data-collection-list').addEventListener('click', function(event) {
    event.stopPropagation(); // Prevent click inside the dropdown from closing it
});

// Update the dropdown box text based on selected checkboxes
document.querySelectorAll('#data-collection-list input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        updateSelectedOptions();
    });
});

// Close the dropdown if the user clicks outside
window.onclick = function(event) {
    if (!event.target.matches('.multiselect-dropdown-box1')) {
        var dropdowns = document.getElementsByClassName('multiselect-dropdown-list1');
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
};

// Function to update the dropdown box text with selected checkboxes
function updateSelectedOptions() {
    var selectedOptions = [];
    document.querySelectorAll('#data-collection-list input[type="checkbox"]:checked').forEach(function(checkbox) {
        selectedOptions.push(checkbox.value);
    });
    
    var dropdownBox = document.getElementById('data-collection-box');
    if (selectedOptions.length > 0) {
        dropdownBox.textContent = selectedOptions.join(', ');
    } else {
        dropdownBox.textContent = 'Select Data Collection Methods';
    }
}
