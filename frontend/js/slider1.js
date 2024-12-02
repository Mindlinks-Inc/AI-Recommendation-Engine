document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('data-quality-rating');
    const value = document.getElementById('data-quality-value');
    const label = document.getElementById('data-quality-label');

    function updateLabel(val) {
        value.textContent = val;
        switch(parseInt(val)) {
            case 1:
                label.textContent = 'Poor';
                break;
            case 2:
                label.textContent = 'Fair';
                break;
            case 3:
                label.textContent = 'Average';
                break;
            case 4:
                label.textContent = 'Good';
                break;
            case 5:
                label.textContent = 'Excellent';
                break;
        }
    }

    slider.addEventListener('input', function() {
        updateLabel(this.value);
    });

    // Initialize label
    updateLabel(slider.value);
});