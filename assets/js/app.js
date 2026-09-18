$(document).ready(function () {

    $(".datatable").DataTable({

        language: {

            url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"

        },

        pageLength: 10,

        order: [[0, "desc"]]

    });

});

// Confirmación para eliminar
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".btn-eliminar").forEach(function (boton) {

        boton.addEventListener("click", function (e) {

            e.preventDefault();

            let url = this.href;

            Swal.fire({
                title: "¿Eliminar registro?",
                text: "Esta acción no se puede deshacer.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#dc3545"
            }).then((result) => {

                if (result.isConfirmed) {
                    window.location.href = url;
                }

            });

        });

    });

});