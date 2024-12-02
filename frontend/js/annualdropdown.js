// Toggle the visibility of Industry Regulations dropdown
document.getElementById('industry-regulations-box').addEventListener('click', function() {
    var dropdown = document.getElementById('industry-regulations-list');
    dropdown.classList.toggle('show');
});

// Toggle the visibility of Data Collection Methods dropdown
document.getElementById('data-collection-box').addEventListener('click', function() {
    var dropdown = document.getElementById('data-collection-list');
    dropdown.classList.toggle('show');
});

// Close the dropdowns if the user clicks outside of them
window.onclick = function(event) {
    if (!event.target.matches('.multiselect-dropdown-box')) {
        var dropdowns = document.getElementsByClassName('multiselect-dropdown-list');
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
};
