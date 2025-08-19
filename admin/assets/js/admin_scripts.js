// admin/assets/js/admin_scripts.js - VERSÃO DE AÇÃO DIRETA

$(document).ready(function() {
    console.log("Admin Scripts Loaded. jQuery is ready.");

    const confirmModal = $('#confirm-modal');
    const modalMessage = $('#modal-message');
    const modalConfirmBtn = $('#modal-confirm-btn');
    const modalCancelBtn = $('#modal-cancel-btn');

    // Ação de clique delegada no documento
    $(document).on('click', '.btn-delete', function(event) {
        event.preventDefault();
        console.log("Delete button clicked!");

        const button = $(this);
        const id = button.data('id');
        const nome = button.data('nome');

        if (id !== undefined && nome !== undefined) {
            const deleteUrl = `produto_deletar.php?id=${id}`;
            const message = `Tem certeza que deseja excluir o produto '${nome}'? Esta ação não pode ser desfeita.`;
            
            // --- MODIFICAÇÃO PRINCIPAL ---
            // Aplica os estilos diretamente para forçar a exibição
            console.log("Forcing modal display with direct styles.");
            modalMessage.text(message);
            confirmModal.css({
                'display': 'flex',
                'opacity': '1'
            });

            // Define a ação do botão "Confirmar"
            modalConfirmBtn.off('click').on('click', function() {
                hideModal();
                window.location.href = deleteUrl;
            });

        } else {
            console.error("Could not find data-id or data-nome on the button.");
        }
    });

    // Função para esconder o modal
    function hideModal() {
        confirmModal.css('opacity', '0');
        setTimeout(() => {
            confirmModal.css('display', 'none');
        }, 300);
    }

    // Eventos para fechar o modal
    modalCancelBtn.on('click', hideModal);
    confirmModal.on('click', function(event) { if (event.target === this) { hideModal(); } });


    // --- LÓGICA TOAST (sem alterações) ---
    function showToast(message, type = 'info') {
        const container = $('#toast-container');
        if (!container.length) return;
        const toast = $('<div class="toast"></div>').addClass(type).text(message);
        container.append(toast);
        setTimeout(() => toast.addClass('show'), 100);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    const params = new URLSearchParams(window.location.search);
    const status = params.get('status');
    const msg = params.get('msg');
    if (status && msg) {
        showToast(decodeURIComponent(msg), status);
        history.replaceState(null, '', window.location.pathname);
    }

});