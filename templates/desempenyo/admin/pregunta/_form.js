// ayudaTipo($('#pregunta_tipo option:selected').val() ?? $('.ejemplos:first-child').data('tipo'));
ayudaTipo($('#pregunta_id_tipo').val() ?? $('.ejemplos:first-child').data('tipo'));
$('#pregunta_tipo').on('change', function () {
    ayudaTipo($(this).val());
});
// Actualizar la ayuda del tipo de pregunta con un ejemplo
function ayudaTipo(tipo) {
    let ejemplo = $('#ejemplo-' + tipo);
    $('#pregunta_tipo_help').html(
        '<span class="m-2 align-top">Ejemplo:</span>' + (ejemplo.html() ?? 'No disponible')
    );
}
