
const API_URL = '/api/followups';
const USER_ID = '1';

const form = document.getElementById('followupForm');
let editingId = null;
const submitButton = document.getElementById('submitButton');
const cancelEditButton = document.getElementById('cancelEditButton');

const rows = document.getElementById('followupRows');
const formMessage = document.getElementById('formMessage');
const listMessage = document.getElementById('listMessage');

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            'X-User-Id': USER_ID,
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...options.headers
        }
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.error || data.message || `خطای درخواست: ${response.status}`);
    }

    return data;
}

function toPersian(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[char]);
}

function statusLabel(status) {
    return status === 'completed' ? 'تکمیل‌شده' : 'باز';
}

function priorityLabel(priority) {
    return ({
        low: 'کم',
        normal: 'عادی',
        high: 'زیاد'
    })[priority] || priority;
}

async function loadFollowups() {
    listMessage.textContent = 'در حال دریافت اطلاعات...';
    rows.replaceChildren();

    try {
        const result = await apiRequest(API_URL);
        const items = Array.isArray(result) ? result : (result.data || []);

        document.getElementById('totalCount').textContent = items.length;
        document.getElementById('openCount').textContent =
            items.filter(item => item.status === 'open').length;
        document.getElementById('completedCount').textContent =
            items.filter(item => item.status === 'completed').length;

        if (!items.length) {
            rows.innerHTML = '<tr><td colspan="7">هنوز پیگیری‌ای ثبت نشده است.</td></tr>';
            listMessage.textContent = '';
            return;
        }

        items.forEach(item => {
            const tr = document.createElement('tr');
            const action = item.status === 'completed' ? 'reopen' : 'complete';
            const actionLabel = item.status === 'completed' ? 'بازگشایی' : 'تکمیل';

            tr.innerHTML = `
                <td>${toPersian(item.id)}</td>
                <td>${toPersian(item.customer_id)}</td>
                <td>${toPersian(item.title)}</td>
                <td>${toPersian(priorityLabel(item.priority))}</td>
                <td>${toPersian(statusLabel(item.status))}</td>
                <td>${toPersian(item.due_at || '—')}</td>
                <td>
                    <button type="button" class="secondary-button"
                        data-action="${action}" data-id="${Number(item.id)}">
                        ${actionLabel}
                    </button>
                      ${item.status === 'open' ? `
        <button type="button" class="secondary-button"
            data-action="edit" data-id="${Number(item.id)}">
            ویرایش
        </button>
    ` : ''}
                </td>
            `;
            
            rows.appendChild(tr);
        });

        listMessage.textContent = '';
    } catch (error) {
        listMessage.textContent = 'دریافت فهرست ناموفق بود: ' + error.message;
        rows.innerHTML = '<tr><td colspan="7">خطا در دریافت اطلاعات</td></tr>';
    }
}

form.addEventListener('submit', async event => {
    event.preventDefault();
    formMessage.textContent = 'در حال ذخیره...';
    submitButton.disabled = true;

    const formData = new FormData(form);
    const dueValue = formData.get('due_at');

    const payload = {
        customer_id: Number(formData.get('customer_id')),
        title: String(formData.get('title')).trim(),
        priority: formData.get('priority'),
        description: String(formData.get('description') || '').trim(),
        due_at: dueValue ? dueValue.replace('T', ' ') + ':00' : ''
    };

    try {
        if (editingId !== null) {
            await apiRequest(`${API_URL}/${editingId}`, {
                method: 'PUT',
                body: JSON.stringify(payload)
            });
            formMessage.textContent = 'تغییرات با موفقیت ذخیره شد.';
        } else {
            await apiRequest(API_URL, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            formMessage.textContent = 'پیگیری با موفقیت ثبت شد.';
        }

        resetEditForm();
        await loadFollowups();
    } catch (error) {
        formMessage.textContent = 'ذخیره انجام نشد: ' + error.message;
    } finally {
        submitButton.disabled = false;
    }
});

function resetEditForm() {
    form.reset();
    editingId = null;
    form.elements.customer_id.readOnly = false;
    submitButton.textContent = 'ثبت پیگیری';
    cancelEditButton.hidden = true;
}

cancelEditButton.addEventListener('click', () => {
    resetEditForm();
    formMessage.textContent = 'ویرایش لغو شد.';
});

rows.addEventListener('click', async event => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const id = button.dataset.id;
    const action = button.dataset.action;

    button.disabled = true;

    try {
        if (action === 'edit') {
            const result = await apiRequest(`${API_URL}/${id}`);
            const item = result.data || result.followup || result;

            form.elements.customer_id.value = item.customer_id;
            form.elements.title.value = item.title;
            form.elements.priority.value = item.priority;
            form.elements.description.value = item.description || '';
            form.elements.due_at.value = item.due_at
                ? item.due_at.replace(' ', 'T').slice(0, 16)
                : '';

            editingId = id;
            form.elements.customer_id.readOnly = true;
            submitButton.textContent = 'ذخیره تغییرات';
            cancelEditButton.hidden = false;
            formMessage.textContent = 'اطلاعات پیگیری را ویرایش کنید.';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });

            return;
        }

        await apiRequest(`${API_URL}/${id}/${action}`, {
            method: 'POST'
        });

        await loadFollowups();
    } catch (error) {
        listMessage.textContent = 'عملیات ناموفق بود: ' + error.message;
    } finally {
        button.disabled = false;
    }
});

document.getElementById('refreshButton').addEventListener('click', loadFollowups);

loadFollowups();
