document.addEventListener("DOMContentLoaded", function () {

    const btnMenu = document.getElementById("btnMenu");
    const sidebar = document.getElementById("sidebar");

    if (btnMenu && sidebar) {

        btnMenu.addEventListener("click", function () {
            sidebar.classList.toggle("show");
        });

  
document.addEventListener("click", function(e){

    if(window.innerWidth < 992){

        if(
            !sidebar.contains(e.target) &&
            !btn.contains(e.target)
        ){
            sidebar.classList.remove("show");
        }

    }

});
}

});