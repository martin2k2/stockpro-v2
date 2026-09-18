let carrito = [];

document.addEventListener("DOMContentLoaded", function () {

    const producto = document.getElementById("producto");
    const cantidad = document.getElementById("cantidad");
    const precio = document.getElementById("precio");
    const agregar = document.getElementById("agregarProducto");

    if (producto) {

        producto.addEventListener("change", function () {

            const opcion =
                producto.options[producto.selectedIndex];

            if (!opcion.value) {

                precio.value = "";
                cantidad.value = 1;

                return;

            }

            const tieneVariantes =
                opcion.getAttribute("data-variantes") === "1";

            precio.value =
                opcion.getAttribute("data-precio") || "";

            cantidad.value = 1;

            cargarVariantes(
                producto.value,
                tieneVariantes
            );

        });

    }

    if (agregar) {

        agregar.addEventListener("click", function () {

            agregarProducto();

        });

    }

    actualizarTotal();

});


function cargarVariantes(productoId, tieneVariantes) {

    const contenedor =
        document.getElementById("contenedorVariantes");

    const variante =
        document.getElementById("variante");

    if (!contenedor || !variante) {
        return;
    }

    variante.innerHTML =
        '<option value="">Seleccionar variante</option>';

    if (!tieneVariantes) {

        contenedor.style.display = "none";

        variante.removeAttribute("required");

        return;

    }

    contenedor.style.display = "block";

    variante.setAttribute("required", "required");


    fetch(
        "buscar_producto.php?id=" +
        encodeURIComponent(productoId)
    )
    .then(function (response) {

        return response.json();

    })
    .then(function (data) {

        if (!data.ok) {

            alert(
                data.mensaje ||
                "No se pudieron cargar las variantes."
            );

            return;

        }

        if (!data.variantes) {
            return;
        }

        data.variantes.forEach(function (v) {

            let texto = "";

            if (v.talle) {
                texto += v.talle;
            }

            if (v.color) {

                texto +=
                    (texto ? " - " : "") +
                    v.color;

            }

            if (!texto) {
                texto = "Variante";
            }

            variante.innerHTML += `

                <option
                    value="${v.id}"
                    data-codigo="${v.codigo || ''}"
                    data-stock="${v.stock}"
                >

                    ${texto}
                    (Stock: ${v.stock})

                </option>

            `;

        });

    })
    .catch(function () {

        alert(
            "Error al cargar los talles y colores."
        );

    });

}


function agregarProducto() {

    const producto =
        document.getElementById("producto");

    const cantidadInput =
        document.getElementById("cantidad");

    const precioInput =
        document.getElementById("precio");

    const variante =
        document.getElementById("variante");


    if (!producto || !producto.value) {

        alert("Seleccione un producto.");

        return;

    }


    const opcion =
        producto.options[producto.selectedIndex];


    const productoId =
        parseInt(producto.value);


    const tieneVariantes =
        opcion.getAttribute("data-variantes") === "1";


    let id;
    let codigo;
    let nombre;
    let stock;
    let varianteId = null;
    let varianteNombre = "";


    nombre =
        opcion.textContent.trim();


    if (tieneVariantes) {

        if (!variante || !variante.value) {

            alert(
                "Seleccione talle, color o variante."
            );

            return;

        }

        const opcionVariante =
            variante.options[
                variante.selectedIndex
            ];


        varianteId =
            parseInt(variante.value);


        id =
            productoId + "_" + varianteId;


        codigo =
            opcionVariante.getAttribute(
                "data-codigo"
            ) ||
            opcion.getAttribute(
                "data-codigo"
            );


        stock =
            parseInt(
                opcionVariante.getAttribute(
                    "data-stock"
                )
            );


        varianteNombre =
            opcionVariante.textContent
                .replace(
                    /\(Stock:.*\)/,
                    ""
                )
                .trim();


    } else {

        id = productoId;

        codigo =
            opcion.getAttribute(
                "data-codigo"
            );

        stock =
            parseInt(
                opcion.getAttribute(
                    "data-stock"
                )
            );

    }


    const cantidad =
        parseInt(cantidadInput.value);


    const precio =
        parseFloat(precioInput.value);


    if (!cantidad || cantidad < 1) {

        alert("Cantidad inválida.");

        return;

    }


    if (!precio || precio <= 0) {

        alert("Precio inválido.");

        return;

    }


    if (cantidad > stock) {

        alert(
            "Stock disponible: " +
            stock
        );

        return;

    }


    const existente =
        carrito.find(function (item) {

            return item.id === id;

        });


    if (existente) {

        if (
            existente.cantidad +
            cantidad >
            stock
        ) {

            alert(
                "No hay suficiente stock."
            );

            return;

        }

        existente.cantidad +=
            cantidad;

    } else {

        carrito.push({

            id: id,

            producto_id: productoId,

            variante_id: varianteId,

            codigo: codigo,

            nombre: nombre,

            variante: varianteNombre,

            cantidad: cantidad,

            precio: precio,

            stock: stock,

            descuento: 0

        });

    }


    producto.value = "";

    cantidadInput.value = 1;

    precioInput.value = "";


    if (variante) {

        variante.innerHTML =
            '<option value="">Seleccionar variante</option>';

    }


    const contenedor =
        document.getElementById(
            "contenedorVariantes"
        );

    if (contenedor) {

        contenedor.style.display =
            "none";

    }


    dibujarCarrito();

}


function dibujarCarrito() {

    const tbody =
        document.querySelector(
            "#tablaVenta tbody"
        );


    if (!tbody) {
        return;
    }


    let html = "";


    if (carrito.length === 0) {

        tbody.innerHTML = `

            <tr>

                <td
                    colspan="6"
                    class="text-center text-muted"
                >

                    No hay productos agregados

                </td>

            </tr>

        `;

        actualizarTotal();

        return;

    }


    carrito.forEach(function (item, index) {

        const subtotal =
            item.cantidad *
            item.precio;


        const detalleNombre =
            item.variante
                ? `${item.nombre}<br>
                   <small class="text-muted">
                   ${item.variante}
                   </small>`
                : item.nombre;


        html += `

            <tr>

                <td>

                    ${item.codigo || "-"}

                </td>


                <td>

                    ${detalleNombre}

                </td>


                <td>

                    <input
                        type="number"
                        min="1"
                        max="${item.stock}"
                        value="${item.cantidad}"
                        class="form-control"
                        onchange="
                            cambiarCantidad(
                                ${index},
                                this.value
                            )
                        "
                    >

                </td>


                <td>

                    $ ${formatear(item.precio)}

                </td>


                <td>

                    $ ${formatear(subtotal)}

                </td>


                <td>

                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        onclick="
                            eliminarProducto(
                                ${index}
                            )
                        "
                    >

                        <i class="bi bi-trash"></i>

                    </button>

                </td>

            </tr>

        `;

    });


    tbody.innerHTML = html;


    actualizarTotal();

}


function actualizarTotal() {

    let total = 0;


    carrito.forEach(function (item) {

        total +=
            item.cantidad *
            item.precio;

    });


    const totalVenta =
        document.getElementById(
            "totalVenta"
        );


    if (totalVenta) {

        totalVenta.textContent =
            "$ " +
            formatear(total);

    }


    const totalGrande =
        document.getElementById(
            "totalGrande"
        );


    if (totalGrande) {

        totalGrande.textContent =
            "$ " +
            formatear(total);

    }


    const detalle =
        document.getElementById(
            "detalle"
        );


    if (detalle) {

        detalle.value =
            JSON.stringify(carrito);

    }

}


function cambiarCantidad(index, valor) {

    let cantidad =
        parseInt(valor);


    const item =
        carrito[index];


    if (!cantidad || cantidad < 1) {

        cantidad = 1;

    }


    if (cantidad > item.stock) {

        alert(
            "Stock disponible: " +
            item.stock
        );

        cantidad = item.stock;

    }


    carrito[index].cantidad =
        cantidad;


    dibujarCarrito();

}


function eliminarProducto(index) {

    carrito.splice(index, 1);

    dibujarCarrito();

}


function formatear(numero) {

    return Number(numero).toLocaleString(
        "es-AR",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );

}