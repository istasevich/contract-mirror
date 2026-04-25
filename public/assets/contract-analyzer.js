function openPaymentModal() {
    const modal = document.getElementById('payment-modal');
    const dialog = document.getElementById('payment-modal-dialog');
    const input = document.getElementById('tx-hash-input');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    setTimeout(() => {
        dialog?.focus();
        input?.focus();
    }, 50);
}

function closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    const input = document.getElementById('tx-hash-input');

    modal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');

    if (input) {
        input.blur();
    }
}
async function submitPayment() {
    const input = document.getElementById('tx-hash-input');
    const txHash = input ? input.value.trim() : '';

    if (!txHash) {
        alert('Enter transaction hash');
        return;
    }

    if (!window.currentPaymentId) {
        alert('Payment not initialized. Please reload the page.');
        return;
    }

    const response = await fetch(`/api/payments/${window.currentPaymentId}/submit`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ txHash }),
    });

    const payload = await response.json();

    if (!payload.success) {
        alert(payload.message || 'Failed to submit payment.');
        return;
    }

    if (payload.status === 'confirmed') {
        location.reload();
        return;
    }

    pollPaymentStatus();
}

async function pollPaymentStatus() {
    let attempts = 0;
    const maxAttempts = 12;

    const interval = setInterval(async () => {
        attempts++;

        const response = await fetch(`/api/payments/${window.currentPaymentId}/status`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json();

        if (payload.success && payload.status === 'confirmed') {
            clearInterval(interval);
            location.reload();
            return;
        }

        if (attempts >= maxAttempts) {
            clearInterval(interval);
            alert('Payment is still pending verification. Please refresh in a minute.');
        }
    }, 5000);
}
async function showPaywall(payload) {
    const reportContent = document.getElementById('report-content');
    const reportLoadingState = document.getElementById('report-loading-state');

    const publicId = window.currentReportId || '';

    const response = await fetch(`/api/paywall/${publicId}`);
    reportContent.innerHTML = await response.text();

    reportLoadingState.classList.add('hidden');
    reportContent.classList.remove('hidden');
}

function startPaymentPolling(publicId) {
    if (!publicId) return;

    console.log('Start polling for:', publicId);

    const interval = setInterval(async () => {
        try {
            const res = await fetch(`/api/reports/${publicId}/status`);
            const data = await res.json();

            console.log('Polling:', data);

            if (!data.isLocked) {
                clearInterval(interval);

                console.log('UNLOCKED');
                location.reload();
            }
        } catch (e) {
            console.error('Polling error', e);
        }
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('analyze-contract-form');
    const trigger = document.getElementById('upload-contract-trigger');
    const input = document.getElementById('contract-file-input');
    const fileName = document.getElementById('selected-file-name');
    const submitButton = document.getElementById('analyze-submit-button');
    const formStatus = document.getElementById('form-status');
    const reportTitle = document.getElementById('report-title');
    const reportSubtitle = document.getElementById('report-subtitle');
    const reportEmptyState = document.getElementById('report-empty-state');
    const reportLoadingState = document.getElementById('report-loading-state');
    const reportContent = document.getElementById('report-content');
    const simpleButton = document.getElementById('simple-view-button');
    const detailedButton = document.getElementById('detailed-view-button');

    let currentView = 'simple';

    const setFormStatus = (message, kind) => {
        if (!formStatus) {
            return;
        }

        if (!message) {
            formStatus.className = 'mt-5 hidden rounded-2xl border px-4 py-3 text-sm';
            formStatus.textContent = '';
            return;
        }

        const tone = kind === 'error'
            ? 'border-red-400/20 bg-red-500/10 text-red-200'
            : 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100';

        formStatus.className = `mt-5 rounded-2xl border px-4 py-3 text-sm ${tone}`;
        formStatus.textContent = message;
    };

    const setViewButtons = () => {
        const simpleActive = currentView === 'simple';
        simpleButton.className = simpleActive
            ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-sm font-bold text-cyan-100'
            : 'rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-bold text-slate-300';
        detailedButton.className = !simpleActive
            ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-sm font-bold text-cyan-100'
            : 'rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-bold text-slate-300';
    };

    const applyView = () => {
        const simpleBlocks = reportContent.querySelectorAll('.js-report-simple');
        const detailedBlocks = reportContent.querySelectorAll('.js-report-detailed');

        simpleBlocks.forEach((element) => {
            element.classList.toggle('hidden', currentView !== 'simple');
        });

        detailedBlocks.forEach((element) => {
            element.classList.toggle('hidden', currentView !== 'detailed');
        });

        setViewButtons();
    };

    if (trigger && input) {
        trigger.addEventListener('click', () => input.click());
    }

    if (input && fileName) {
        input.addEventListener('change', () => {
            const file = input.files && input.files[0] ? input.files[0] : null;
            fileName.textContent = file ? file.name : 'No file selected';
            setFormStatus('', 'info');
        });
    }

    simpleButton.addEventListener('click', () => {
        currentView = 'simple';
        applyView();
    });

    detailedButton.addEventListener('click', () => {
        currentView = 'detailed';
        applyView();
    });

    if (!form) {
        return;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!input.files || !input.files.length) {
            setFormStatus('Please choose a contract file first.', 'error');
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Analyzing...';
        setFormStatus('Analyzing contract and preparing the report...', 'info');

        reportEmptyState.classList.add('hidden');
        reportContent.classList.add('hidden');
        reportLoadingState.classList.remove('hidden');

        try {
            const formData = new FormData(form);

            const response = await fetch('/api/contracts/analyze', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();

            if (payload.usage) {
                const counter = document.getElementById('usage-counter');

                if (counter) {
                    const { remaining } = payload.usage;

                    counter.innerText = `Free analyses left: ${remaining} / 5`;

                    if (remaining <= 1) {
                        counter.classList.add('text-orange-400');
                    }

                    if (remaining === 0) {
                        counter.classList.remove('text-orange-400');
                        counter.classList.add('text-red-400');
                    }
                }
            }

            window.currentReportId = payload.publicId || null;

            if (response.status === 403 && payload.error === 'Free limit reached') {

                if (payload.publicId) {
                    window.history.pushState({}, '', `/r/${payload.publicId}`);
                }

                showPaywall(payload);
                startPaymentPolling(window.currentReportId);
                return;
            }


            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Failed to analyze the contract.');
            }

            window.currentPaymentId = payload.payment?.paymentId || null;
            window.currentPaymentAddress = payload.payment?.address || null;
            window.currentPaymentAmount = payload.payment?.amount || null;
            window.currentPaymentCurrency = payload.payment?.currency || null;

            const paymentAddress = document.getElementById('payment-modal-address');
            const paymentAmountText = document.getElementById('payment-modal-amount-text');

            if (paymentAddress) {
                paymentAddress.textContent = window.currentPaymentAddress || 'Wallet address unavailable';
            }

            if (paymentAmountText) {
                const amount = window.currentPaymentAmount || '5.00';
                const currency = window.currentPaymentCurrency || 'USDT';
                paymentAmountText.textContent = `${amount} ${currency}`;
            }

            if (payload.publicId) {
                localStorage.setItem('lastReportPublicId', payload.publicId);
                window.history.pushState({}, '', `/r/${payload.publicId}`);
            }

            reportTitle.textContent = payload.reportTitle || 'Contract analysis report';
            reportSubtitle.textContent = payload.reportSubtitle || 'Review the result below.';
            reportContent.innerHTML = payload.html || '';

            reportLoadingState.classList.add('hidden');
            reportContent.classList.remove('hidden');

            currentView = 'simple';
            applyView();

            setFormStatus('Report generated successfully.', 'info');
            document.getElementById('live-report').scrollIntoView({ behavior: 'smooth', block: 'start' });

        } catch (error) {
            console.error(error);
            reportLoadingState.classList.add('hidden');
            reportEmptyState.classList.remove('hidden');
            reportContent.classList.add('hidden');
            setFormStatus(error.message || 'Unexpected error.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Analyze contract';
        }


    });

    const paymentModal = document.getElementById('payment-modal');
    const paymentDialog = document.getElementById('payment-modal-dialog');

    paymentModal?.addEventListener('click', (event) => {
        if (event.target === paymentModal) {
            closePaymentModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && paymentModal && !paymentModal.classList.contains('hidden')) {
            closePaymentModal();
        }
    });
});
