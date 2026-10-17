
const API_URL = '/api/followups';

const currentUserSelect = document.getElementById('currentUser');
let USER_ID = currentUserSelect
    ? currentUserSelect.value
    : '1';

const form = document.getElementById('followupForm');
let editingId = null;
let currentPage = 1;
const pageSize = 5;
let totalPages = 1;
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

function formatPersianDateTime(value) {
    if (!value || value === '—') return '—';

    const match = String(value).match(
        /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/
    );

    if (!match) return toPersian(value);

    const [, year, month, day, hour = '00', minute = '00'] = match;

    const date = new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
        Number(hour),
        Number(minute)
    );

    return new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-arabext', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(date) + ' - ' +
        new Intl.NumberFormat('fa-IR-u-nu-arabext', {
            minimumIntegerDigits: 2,
            useGrouping: false
        }).format(Number(hour)) + ':' +
        new Intl.NumberFormat('fa-IR-u-nu-arabext', {
            minimumIntegerDigits: 2,
            useGrouping: false
        }).format(Number(minute));
}

async function loadFollowups() {
    listMessage.textContent = 'در حال دریافت اطلاعات...';
    rows.replaceChildren();

    try {
      const params = new URLSearchParams({
    limit: pageSize,
    offset: (currentPage - 1) * pageSize
});

const title = document.getElementById('filterTitle').value.trim();
const priority = document.getElementById('filterPriority').value;
const status = document.getElementById('filterStatus').value;

if (title) params.set('title', title);
if (priority) params.set('priority', priority);
if (status) params.set('status', status);

const url = `${API_URL}?${params.toString()}`;
const result = await apiRequest(url);
       console.log('Pagination result:', result);
        const items = Array.isArray(result) ? result : (result.data || []);
        const total = result.pagination?.total ?? items.length;

        totalPages = Math.max(1, Math.ceil(total / pageSize));

        document.getElementById('pageInfo').textContent =
        `صفحه ${currentPage} از ${totalPages}`;

        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= totalPages;

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
                <td>${formatPersianDateTime(item.due_at)}</td>
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
document.getElementById('prevPage').addEventListener('click', async () => {
    if (currentPage > 1) {
        currentPage--;
        await loadFollowups();
    }
});

document.getElementById('nextPage').addEventListener('click', async () => {
    if (currentPage < totalPages) {
        currentPage++;
        await loadFollowups();
    }
});

const filterModal = document.getElementById('filterModal');

document.getElementById('openFilterButton').addEventListener('click', () => {
    filterModal.hidden = false;
});

document.getElementById('closeFilterButton').addEventListener('click', () => {
    filterModal.hidden = true;
});

document.getElementById('applyFilters').addEventListener('click', async () => {
    currentPage = 1;
    filterModal.hidden = true;
    await loadFollowups();
});

document.getElementById('clearFilters').addEventListener('click', async () => {
    document.getElementById('filterTitle').value = '';
    document.getElementById('filterPriority').value = '';
    document.getElementById('filterStatus').value = '';

    currentPage = 1;
    filterModal.hidden = true;
    await loadFollowups();
});

document.addEventListener('DOMContentLoaded', () => {
    const hiddenInput = document.getElementById('due_at');

    if (!hiddenInput || typeof AzarDatepicker === 'undefined') {
        console.error('تقویم شمسی بارگذاری نشده است.');
        return;
    }

    new AzarDatepicker({
        selector: '#dueAtPicker',
        calendar: 'jalali',
        mode: 'datetime',
        inputFormat: 'YYYY/MM/DD HH:mm',
        placeholder: 'انتخاب تاریخ و ساعت شمسی',
        rtl: true,

        onChange: function (data) {
            if (!data.nativeDate) return;

            const date = data.nativeDate;
            const pad = number => String(number).padStart(2, '0');

            hiddenInput.value =
                `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ` +
                `${pad(date.getHours())}:${pad(date.getMinutes())}:00`;
        },

        onClear: function () {
            hiddenInput.value = '';
        }
    });
});

if (currentUserSelect) {
    currentUserSelect.addEventListener('change', async function () {
        USER_ID = currentUserSelect.value;
        currentPage = 1;

        await loadFollowups();
    });
}


loadFollowups();
