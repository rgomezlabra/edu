import jQuery from 'jquery';

// Usar letras sin acentuar para búsquedas incluyendo acentos en DataTables.
function removeAccents ( data ) {
    if ( data.normalize ) {
        return data +' '+ data
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }
    return data;
}

let searchType = jQuery.fn.DataTable.ext.type.search;

searchType.string = function ( data ) {
    return ! data ?
        '' :
        typeof data === 'string' ?
            removeAccents( data ) :
            data;
};

searchType.html = function ( data ) {
    return ! data ?
        '' :
        typeof data === 'string' ?
            removeAccents( data.replace( /<.*?>/g, '' ) ) :
            data;
};
