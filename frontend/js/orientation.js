document.addEventListener("DOMContentLoaded", function() {
    const dropdownMenuButton = document.getElementById("dropdownMenuButton");
    const selectedTitle = document.getElementById("selected-title");
    const dropdownItems = document.querySelectorAll(".dropdown-item");
  
    dropdownItems.forEach((item) => {
      item.addEventListener("click", function() {
        selectedTitle.textContent = this.getAttribute("data-value");
      });
    });
  });