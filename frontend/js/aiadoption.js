// Toggle the visibility of Primary AI Adoption Concerns dropdown
document.getElementById('ai-concerns-box').addEventListener('click', function() {
    var dropdown = document.getElementById('ai-concerns-list');
    dropdown.classList.toggle('show');
});

// Prevent dropdown from closing when clicking inside (on the checkboxes)
document.getElementById('ai-concerns-list').addEventListener('click', function(event) {
    event.stopPropagation(); // Prevent click inside the dropdown from closing it
});

// Update the dropdown box text based on selected checkboxes
document.querySelectorAll('#ai-concerns-list input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        updateSelectedConcerns();
    });
});

// Close the dropdown if the user clicks outside
window.onclick = function(event) {
    if (!event.target.matches('.multiselect-dropdown-box4')) {
        var dropdowns = document.getElementsByClassName('multiselect-dropdown-list4');
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
};

// Function to update the dropdown box text with selected checkboxes
function updateSelectedConcerns() {
    var selectedOptions = [];
    document.querySelectorAll('#ai-concerns-list input[type="checkbox"]:checked').forEach(function(checkbox) {
        selectedOptions.push(checkbox.value);
    });
    
    var dropdownBox = document.getElementById('ai-concerns-box');
    if (selectedOptions.length > 0) {
        dropdownBox.textContent = selectedOptions.join(', ');
    } else {
        dropdownBox.textContent = 'Select Primary AI Adoption Concerns';
    }
}
