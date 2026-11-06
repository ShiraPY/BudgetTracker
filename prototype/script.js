// Data & Initialization
let B = +localStorage.budget || 5000;
let E = JSON.parse(localStorage.expenses || 'null') || [
    { id: 1, date: '2026-09-15', d: 'Food', c: 'Food', a: 150 },
    { id: 2, date: '2026-09-14', d: 'Transportation', c: 'Transportation', a: 100 },
    { id: 3, date: '2026-09-13', d: 'School Supplies', c: 'School', a: 450 },
    { id: 4, date: '2026-09-12', d: 'Entertainment', c: 'Entertainment', a: 200 }
];
let I = JSON.parse(localStorage.income || 'null') || [
    { date: '2026-09-01', s: 'Allowance', a: 5000 }
];
const C = ['Food', 'Transportation', 'School', 'Personal Needs', 'Entertainment', 'Others'];

// Helper Functions
const $ = x => document.querySelector(x);
const $$ = x => document.querySelectorAll(x);

const money = x => '₱' + (+x).toLocaleString('en-PH', {
    minimumFractionDigits: 2
});

// Storage
function save() {
    localStorage.budget = B;
    localStorage.expenses = JSON.stringify(E);
    localStorage.income = JSON.stringify(I);
}

// Auth
function start(e) {
    e.preventDefault();
    localStorage.logged = 1;
    localStorage.name = $('#email').value.split('@')[0];
    openApp();
}

function register(e) {
    e.preventDefault();
    localStorage.logged = 1;
    localStorage.name = $('#name').value;
    openApp();
}

function openApp() {
    $('#login').classList.add('hide');
    $('#app').classList.remove('hide');
    let n = localStorage.name || 'Student';
    $('#user').textContent = n;
    $('#hello').textContent = n;
    render();
}

function tab(n) {
    $('#lf').classList.toggle('hide', n);
    $('#rf').classList.toggle('hide', !n);
}

// Check existing session
if (localStorage.logged) openApp();

// Navigation
$$('[data-p]').forEach(b => {
    b.onclick = () => go(b.dataset.p);
});

function go(p) {
    $$('.page').forEach(x => {
        x.classList.toggle('active', x.id === p);
    });
    $$('nav button').forEach(x => {
        x.classList.toggle('active', x.dataset.p === p);
    });
    render();
}

// Logout & Mobile Menu
$('#out').onclick = () => {
    localStorage.removeItem('logged');
    location.reload();
};

$('#menu').onclick = () => {
    $('aside').classList.toggle('open');
};

// Render All Data
function render() {
    let ti = I.reduce((s, x) => s + x.a, 0);
    let te = E.reduce((s, x) => s + x.a, 0);

    $('#inc').textContent = money(ti);
    $('#exp').textContent = money(te);
    $('#rem').textContent = money(B - te);
    $('#bud').textContent = money(B);
    $('#bb').textContent = money(B);

    let p = B ? Math.min(100, te / B * 100) : 0;
    $('#bar').style.width = p + '%';
    $('#bbbar').style.width = p + '%';
    $('#pct').textContent = p.toFixed(0) + '% used';
    $('#bbtext').textContent = `${money(te)} spent • ${money(Math.max(0, B - te))} remaining`;

    $('#recent').innerHTML = E.slice().reverse().slice(0, 5).map(x => `
        <div class="item">
            <b>${x.d}</b> — ${money(x.a)}<br>
            <small>${x.c} • ${x.date}</small>
        </div>
    `).join('');

    $('#etable').innerHTML = E.map(x => `
        <tr>
            <td>${x.date}</td>
            <td>${x.d}</td>
            <td>${x.c}</td>
            <td>${money(x.a)}</td>
            <td>
                <button onclick="editE(${x.id})">Edit</button>
                <button onclick="delE(${x.id})">Delete</button>
            </td>
        </tr>
    `).join('');

    $('#ilist').innerHTML = I.map(x => `
        <div class="item">
            <b>${x.s}</b> — ${money(x.a)}<br>
            ${x.date}
        </div>
    `).join('');

    $('#categories').innerHTML = C.map(c => {
        let total = E.filter(x => x.c === c).reduce((s, x) => s + x.a, 0);
        return `<div>
            <small>${c}</small>
            <b>${money(total)}</b>
        </div>`;
    }).join('');

    $('#summary').innerHTML = `
        <p>Total Income: <b>${money(ti)}</b></p>
        <p>Total Expenses: <b>${money(te)}</b></p>
        <p>Budget: <b>${money(B)}</b></p>
        <p>Remaining: <b>${money(B - te)}</b></p>
    `;

    $('#bars').innerHTML = C.map(c => {
        let n = E.filter(x => x.c === c).reduce((s, x) => s + x.a, 0);
        let percent = Math.min(100, n / (B || 1) * 100);
        return `<div class="item">
            ${c} — ${money(n)}
            <div class="bar">
                <i style="width:${percent}%"></i>
            </div>
        </div>`;
    }).join('');

    $('#notes').innerHTML = `
        <div class="notice">
            🔔 You have <b>${money(Math.max(0, B - te))}</b> remaining from your budget.
        </div>
        <div class="notice">
            💡 Review your recent transactions regularly.
        </div>
    `;

    $('#setname').value = localStorage.name || 'Student';
    draw();
}

// Expense Management
function expense(edit) {
    let x = edit || {};
    let today = new Date().toISOString().slice(0, 10);
    
    modal('Expense', `
        <label>Description</label>
        <input id="md" required value="${x.d || ''}">
        
        <label>Category</label>
        <select id="mc">
            ${C.map(c => `<option ${x.c === c ? 'selected' : ''}>${c}</option>`).join('')}
        </select>
        
        <label>Amount</label>
        <input id="ma" type="number" required value="${x.a || ''}">
        
        <label>Date</label>
        <input id="mdate" type="date" required value="${x.date || today}">
        
        <button>Save Expense</button>
    `, () => {
        let o = {
            id: x.id || Date.now(),
            d: $('#md').value,
            c: $('#mc').value,
            a: +$('#ma').value,
            date: $('#mdate').value
        };
        
        E = x.id ? E.map(z => z.id === x.id ? o : z) : [...E, o];
        save();
        closeM();
        render();
    });
}

function editE(id) {
    expense(E.find(x => x.id === id));
}

function delE(id) {
    E = E.filter(x => x.id !== id);
    save();
    render();
}

// Income Management
function income() {
    let today = new Date().toISOString().slice(0, 10);
    
    modal('Add Income', `
        <label>Source</label>
        <input id="ms" required placeholder="Allowance">
        
        <label>Amount</label>
        <input id="ma" type="number" required>
        
        <label>Date</label>
        <input id="mdate" type="date" required value="${today}">
        
        <button>Save Income</button>
    `, () => {
        I.push({
            s: $('#ms').value,
            a: +$('#ma').value,
            date: $('#mdate').value
        });
        save();
        closeM();
        render();
    });
}

// Budget Management
function budget() {
    modal('Set Budget', `
        <label>Monthly Budget</label>
        <input id="mb" type="number" value="${B}" required>
        <button>Save Budget</button>
    `, () => {
        B = +$('#mb').value;
        save();
        closeM();
        render();
    });
}

// Modal
function modal(t, f, fn) {
    $('#modal').classList.remove('hide');
    $('#mt').textContent = t;
    $('#mf').innerHTML = f;
    $('#mf').onsubmit = e => {
        e.preventDefault();
        fn();
    };
}

function closeM() {
    $('#modal').classList.add('hide');
}

// Settings
function saveName() {
    localStorage.name = $('#setname').value;
    $('#user').textContent = localStorage.name;
    $('#hello').textContent = localStorage.name;
}

// Search & Filter
$('#eq').oninput = () => {
    let q = $('#eq').value.toLowerCase();
    $$('#etable tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
};

$('#cat').onchange = () => {
    let selectedCat = $('#cat').value;
    $$('#etable tr').forEach(r => {
        let rowCat = r.children[2].textContent;
        r.style.display = !selectedCat || rowCat === selectedCat ? '' : 'none';
    });
};

// Pie Chart
function draw() {
    let c = $('#pie');
    let x = c.getContext('2d');
    let data = C.map(cat => {
        return E.filter(e => e.c === cat).reduce((s, e) => s + e.a, 0);
    }).filter(Boolean);
    
    let sum = data.reduce((a, b) => a + b, 0) || 1;
    let start = -Math.PI / 2;
    let cols = ['#0879dc', '#18a66d', '#ef9b2d', '#8251dc', '#e65050', '#56a3d9'];

    x.clearRect(0, 0, 300, 220);
    
    data.forEach((n, i) => {
        let a = n / sum * Math.PI * 2;
        x.beginPath();
        x.moveTo(150, 110);
        x.arc(150, 110, 80, start, start + a);
        x.fillStyle = cols[i % 6];
        x.fill();
        start += a;
    });
}