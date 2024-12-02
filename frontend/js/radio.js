document.addEventListener('DOMContentLoaded', function() {
    const yesRadio = document.getElementById('existing-ai-ml-yes');
    const noRadio = document.getElementById('existing-ai-ml-no');
    const specifyField = document.getElementById('specify-ai-ml');

    function toggleSpecifyField() {
        specifyField.style.display = yesRadio.checked ? 'block' : 'none';
    }

    yesRadio.addEventListener('change', toggleSpecifyField);
    noRadio.addEventListener('change', toggleSpecifyField);

    // Ensure the field is hidden initially
    specifyField.style.display = 'none';
});