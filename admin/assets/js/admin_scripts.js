// admin/assets/js/admin_scripts.js

// Lógica para o Modal de Confirmação
const confirmModal = document.getElementById('confirm-modal');
const modalTitle = document.getElementById('modal-title');
const modalMessage = document.getElementById('modal-message');
const modalConfirmBtn = document.getElementById('modal-confirm-btn');
const modalCancelBtn = document.getElementById('modal-cancel-btn');

function showConfirmModal(message, onConfirm) {
    modalMessage.textContent = message;
    confirmModal.style.display = 'flex';
    setTimeout(() => confirmModal.classList.add('visible'), 10);

    // Cria uma função para o evento de clique para que possamos removê-la depois
    const confirmHandler = () => {
        hideModal();
        onConfirm();
    };

    const cancelHandler = () => {
        hideModal();
    };
    
    // Limpa ouvintes antigos e adiciona os novos
    modalConfirmBtn.replaceWith(modalConfirmBtn.cloneNode(true));
    document.getElementById('modal-confirm-btn').addEventListener('click', confirmHandler);

    modalCancelBtn.replaceWith(modalCancelBtn.cloneNode(true));
    document.getElementById('modal-cancel-btn').addEventListener('click', cancelHandler);
}

function hideModal() {
    confirmModal.classList.remove('visible');
    setTimeout(() => {
        confirmModal.style.display = 'none';
    }, 300); // Aguarda a transição de opacidade terminar
}

// --- LÓGICA PARA AS NOTIFICAÇÕES "TOAST" ---

// Função que cria e exibe uma notificação
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    // Faz o toast aparecer
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    // Faz o toast desaparecer após 5 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        // Remove o elemento da DOM após a animação de saída
        setTimeout(() => {
            toast.remove();
        }, 500);
    }, 5000);
}

// Verifica se há uma mensagem na URL quando a página carrega
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const status = params.get('status');
    const msg = params.get('msg');

    if (status && msg) {
        showToast(decodeURIComponent(msg), status);
        // Limpa a URL para que o toast não reapareça no reload
        history.replaceState(null, '', window.location.pathname);
    }
});