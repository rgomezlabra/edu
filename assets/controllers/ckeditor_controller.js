import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        // Evitar duplicados
        if (this.element.getAttribute('activo')) {
            return;
        }
        this.element.setAttribute('activo', true);
        const config = {
            licenseKey: 'GPL',
        };
        ClassicEditor
            .create(this.element, config)
            .catch(error => {
                console.error(error);
            })
        ;
    }
}
