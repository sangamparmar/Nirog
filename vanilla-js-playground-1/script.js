// Counter functionality
let count = 0;
const counterValue = document.getElementById('counterValue');
const incrementBtn = document.getElementById('incrementBtn');
const decrementBtn = document.getElementById('decrementBtn');

incrementBtn.addEventListener('click', () => {
    count++;
    updateCounter();
});

decrementBtn.addEventListener('click', () => {
    count--;
    updateCounter();
});

function updateCounter() {
    counterValue.textContent = count;
    counterValue.style.color = count > 0 ? '#48bb78' : count < 0 ? '#e53e3e' : '#2d3748';
}

// Todo list functionality
const todoInput = document.getElementById('todoInput');
const addBtn = document.getElementById('addBtn');
const todoList = document.getElementById('todoList');

let todos = [];
let todoIdCounter = 1;

addBtn.addEventListener('click', addTodo);
todoInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        addTodo();
    }
});

function addTodo() {
    const text = todoInput.value.trim();
    if (text === '') {
        alert('Please enter a task!');
        return;
    }
    
    const todo = {
        id: todoIdCounter++,
        text: text,
        completed: false
    };
    
    todos.push(todo);
    todoInput.value = '';
    renderTodos();
}

function toggleTodo(id) {
    const todo = todos.find(t => t.id === id);
    if (todo) {
        todo.completed = !todo.completed;
        renderTodos();
    }
}

function deleteTodo(id) {
    todos = todos.filter(t => t.id !== id);
    renderTodos();
}

function renderTodos() {
    todoList.innerHTML = '';
    
    todos.forEach(todo => {
        const li = document.createElement('li');
        li.className = `todo-item ${todo.completed ? 'completed' : ''}`;
        
        li.innerHTML = `
            <input type="checkbox" ${todo.completed ? 'checked' : ''} 
                   onchange="toggleTodo(${todo.id})">
            <span class="todo-text">${todo.text}</span>
            <div class="todo-actions">
                <button class="complete-btn" onclick="toggleTodo(${todo.id})">
                    ${todo.completed ? 'Undo' : 'Complete'}
                </button>
                <button onclick="deleteTodo(${todo.id})">Delete</button>
            </div>
        `;
        
        todoList.appendChild(li);
    });
}

// Add some sample data
document.addEventListener('DOMContentLoaded', () => {
    console.log('Vanilla JavaScript App loaded successfully!');
    
    // Add a welcome message after 2 seconds
    setTimeout(() => {
        console.log('Try the counter and todo list features!');
    }, 2000);
});