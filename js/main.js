// ===== 1. Hàm tiện ích =====

// Định dạng số thành tiền Việt: 250000 -> "250.000 đ"
function formatPrice(amount) {
    return amount.toLocaleString("vi-VN") + " đ";
}

// ===== 2. Giỏ hàng lưu tạm trong trình duyệt =====
// Giỏ hàng là một đối tượng: { "mã sản phẩm": số lượng }
// Ví dụ: { "1": 2, "4": 1 } nghĩa là 2 sản phẩm mã 1 và 1 sản phẩm mã 4.

const CART_KEY = "minishop_cart";

function getCart() {
    const data = localStorage.getItem(CART_KEY);
    return data ? JSON.parse(data) : {};
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function updateCartCount() {
    const cart = getCart();
    let count = 0;
    for (const id in cart) {
        count += cart[id];
    }
    const badge = document.querySelector("#cart-count");
    if (badge) {
        badge.textContent = count;
    }
}

function updateCartTotals(table) {
    let total = 0;
    const cart = getCart();
    const products = window.MINISHOP_PRODUCTS || [];
    const productsById = {};
    products.forEach(function (product) {
        productsById[String(product.id)] = product;
    });

    const tbody = table.querySelector("tbody");
    tbody.replaceChildren();

    Object.keys(cart).forEach(function (id) {
        const product = productsById[id];
        const quantity = Number(cart[id]);
        if (!product || !Number.isInteger(quantity) || quantity < 1) {
            return;
        }

        const row = document.createElement("tr");
        row.dataset.price = String(product.price);

        const name = document.createElement("td");
        name.textContent = product.name;
        row.appendChild(name);

        const price = document.createElement("td");
        price.textContent = formatPrice(Number(product.price));
        row.appendChild(price);

        const quantityCell = document.createElement("td");
        const input = document.createElement("input");
        input.type = "number";
        input.className = "qty";
        input.min = "1";
        input.max = String(Math.max(1, Number(product.stock)));
        input.value = String(quantity);
        input.dataset.id = id;
        quantityCell.appendChild(input);
        row.appendChild(quantityCell);

        const lineTotalCell = document.createElement("td");
        lineTotalCell.className = "line-total";
        lineTotalCell.textContent = formatPrice(Number(product.price) * quantity);
        row.appendChild(lineTotalCell);

        const removeCell = document.createElement("td");
        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "remove-from-cart";
        removeButton.dataset.id = id;
        removeButton.textContent = "Xóa";
        removeCell.appendChild(removeButton);
        row.appendChild(removeCell);

        tbody.appendChild(row);
        total += Number(product.price) * quantity;
    });

    if (tbody.children.length === 0) {
        const emptyRow = document.createElement("tr");
        const emptyCell = document.createElement("td");
        emptyCell.colSpan = 5;
        emptyCell.textContent = "Giỏ hàng đang trống.";
        emptyRow.appendChild(emptyCell);
        tbody.appendChild(emptyRow);
    }

    document.querySelector("#cart-total").textContent = formatPrice(total);
    const cartJson = document.querySelector("#cart-json");
    if (cartJson) {
        const validCart = {};
        Object.keys(cart).forEach(function (id) {
            const product = productsById[id];
            const quantity = Number(cart[id]);
            if (product && Number.isInteger(quantity) && quantity > 0) {
                validCart[id] = quantity;
            }
        });
        cartJson.value = JSON.stringify(validCart);
    }
    const checkoutButton = document.querySelector("#checkout-button");
    if (checkoutButton) {
        checkoutButton.disabled = total === 0;
    }
}

function addToCart(id) {
    const cart = getCart();
    cart[id] = (cart[id] || 0) + 1;
    saveCart(cart);
    updateCartCount();
}

// Gắn sự kiện click cho mọi nút "Thêm vào giỏ"
document.querySelectorAll(".add-to-cart").forEach(function (button) {
    button.addEventListener("click", function () {
        addToCart(button.dataset.id);
    });
});

const clearCartMarker = document.querySelector("[data-clear-cart]");
if (clearCartMarker) {
    localStorage.removeItem(CART_KEY);
}

// Hiện số lượng ngay khi trang vừa mở
updateCartCount();

const cartTable = document.querySelector(".cart-table");
if (cartTable) {
    cartTable.addEventListener("input", function (event) {
        const input = event.target.closest(".qty");
        if (!input) {
            return;
        }
        const cart = getCart();
        const id = input.dataset.id;
        const max = Number(input.max);
        const quantity = Math.min(max, Math.max(1, Number(input.value) || 1));
        input.value = String(quantity);
        cart[id] = quantity;
        saveCart(cart);
        updateCartCount();
        updateCartTotals(cartTable);
    });
    cartTable.addEventListener("click", function (event) {
        const button = event.target.closest(".remove-from-cart");
        if (!button) {
            return;
        }
        const cart = getCart();
        delete cart[button.dataset.id];
        saveCart(cart);
        updateCartCount();
        updateCartTotals(cartTable);
    });
    const checkoutForm = document.querySelector("#checkout-form");
    checkoutForm.addEventListener("submit", function (event) {
        if (!Object.keys(getCart()).length) {
            event.preventDefault();
        }
        document.querySelector("#cart-json").value = JSON.stringify(getCart());
    });
    updateCartTotals(cartTable);
}

// ===== 4. Form đăng ký: hai mật khẩu phải khớp nhau =====
const registerForm = document.querySelector("#register-form");
if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
        const password = document.querySelector("#password").value;
        const password2 = document.querySelector("#password2").value;
        const error = document.querySelector("#password-error");

        if (password !== password2) {
            event.preventDefault();
            error.textContent = "Hai mật khẩu không khớp nhau.";
        } else {
            error.textContent = "";
        }
    });
}
