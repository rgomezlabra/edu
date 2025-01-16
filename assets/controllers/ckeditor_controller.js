import { Controller } from "@hotwired/stimulus";
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import translations from 'ckeditor5/translations/es.js';

export default class extends Controller {
    connect() {
        // Evitar duplicados
        if (this.element.getAttribute('activo')) {
            return;
        }
        this.element.setAttribute('activo', true);
        const config = {
            language: {
                content: 'es',
            },
            licenseKey: 'GPL',
            removePlugins: [ 'EasyImage', 'ImageUpload' ],
            translations,
        };
        ClassicEditor
            .create(this.element, config)
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                console.error(error);
            })
        ;
    }
}
