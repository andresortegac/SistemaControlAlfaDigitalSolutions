export function initializeConfirmations() {
    document.querySelectorAll('form').forEach((form) => {
        const deleting = form.querySelector('input[name="_method"]')?.value?.toUpperCase() === 'DELETE';
        if (!deleting && !form.hasAttribute('data-confirm')) return;
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirm || '¿Estás seguro?';

            if (window.Swal) {
                event.preventDefault();
                const dark = document.documentElement.dataset.bsTheme === 'dark';
                window.Swal.fire({
                    icon: deleting ? 'warning' : 'question',
                    title: deleting ? '¿Eliminar registro?' : 'Confirmar operación',
                    text: deleting ? 'Esta acción no se puede deshacer.' : message,
                    showCancelButton: true,
                    confirmButtonText: deleting ? 'Sí, eliminar' : 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    background: dark ? '#101722' : '#fff',
                    color: dark ? '#e4ebf5' : '#263449',
                    confirmButtonColor: '#2867bc',
                    customClass: { popup: 'alfa-swal' },
                }).then((result) => {
                    if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
                });
                return;
            }

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}
