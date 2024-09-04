// Definir botón para añadir nuevas respuestas de tipos cerrados
const preguntas = $('#pregunta_opciones_tipo_respuestas');
preguntas.append(
    $('<div>').addClass('badge-primary nueva').append(
        $('<em>').addClass('fas fa-plus').prop('title', 'Nueva respuesta')
    ).on('click', function () {
        $(this).before(preguntas.data('prototype').replace(/__name__/g, preguntas.find('input').length));
        preguntas.find('input:last').parent().parent().append(botonMenos());
    })
);
// Definir botones para quitar respuestas
$('input[id^=pregunta_opciones_tipo_respuestas_]').each(function() {
    $(this).parent().parent().append(botonMenos());
});

// Definir botón menos
function botonMenos() {
    return $('<div>').addClass('col-1').append(
        $('<div>').addClass('badge-primary quitar').append(
            $('<em>').addClass('fas fa-minus').prop('title', 'Quitar respuesta')
        ).on('click', function () {
            $(this).parent().parent().remove();
        })
    );
}
