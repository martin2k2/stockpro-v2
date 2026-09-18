async function imprimirTicket(idVenta){

    try{

        console.log("Iniciando impresión...");

        if(!qz.websocket.isActive()){
            await qz.websocket.connect();
        }

        console.log("Obteniendo ticket...");

        // Obtener el ticket generado por PHP
        const respuesta = await fetch("/stockpro/ventas/ticket.php?id=" + idVenta);

        const html = await respuesta.text();

        console.log("Ticket obtenido");

        const config = qz.configs.create("EPSON TM-T20 Receipt");

        await qz.print(config,[{
            type:'html',
            format:'plain',
            data:html
        }]);

        console.log("Impresión enviada");

    }catch(error){

        //console.error(error);
        //alert("No fue posible imprimir el ticket.");
        console.error("ERROR:", error);

        Swal.fire({
            icon: "error",
            title: "Error al imprimir",
            html: "<pre>" + JSON.stringify(error, null, 2) + "</pre>"
        });
    }



    console.log("QZ activo:", qz.websocket.isActive());

if(!qz.websocket.isActive()){
    await qz.websocket.connect();
    console.log("QZ conectado");
}

const respuesta = await fetch("/stockpro/ventas/ticket.php?id=" + idVenta);

console.log("HTTP:", respuesta.status);

const html = await respuesta.text();

console.log(html.substring(0,200));
}
