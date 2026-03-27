
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

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Failed to analyze the contract.');
            }

            window.currentPaymentId = payload.payment.paymentId;
            window.currentReportId = payload.publicId;

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

        async function pollPaymentStatus() {
            let attempts = 0;

            const interval = setInterval(async () => {
                attempts++;

                const res = await fetch(`/api/payments/${window.currentPaymentId}/status`);
                const data = await res.json();

                if (data.status === 'confirmed') {
                    clearInterval(interval);
                    location.reload();
                }

                if (attempts > 12) {
                    clearInterval(interval);
                    alert('Still verifying. Try refresh later.');
                }
            }, 7000);
        }

        function openPaymentModal() {
            document.getElementById('payment-modal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('payment-modal').classList.add('hidden');
        }

        async function submitPayment() {
            const txHash = document.getElementById('tx-hash-input').value;

            if (!txHash) {
                alert('Enter transaction hash');
                return;
            }

            const response = await fetch(`/api/payments/${window.currentPaymentId}/submit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ txHash })
            });

            const data = await response.json();

            if (data.success) {
                alert('Payment submitted, verifying...');
                pollPaymentStatus();
            }
        }
    });
});
