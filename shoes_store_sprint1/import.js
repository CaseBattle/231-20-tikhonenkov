const sqlite3 = require("sqlite3").verbose();
const bcrypt = require("bcrypt");
const XLSX = require("xlsx");

const db = new sqlite3.Database("shoes.db");

function readExcel(path) {
    const workbook = XLSX.readFile(path);
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    return XLSX.utils.sheet_to_json(sheet, { defval: null });
}

function runAsync(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.run(sql, params, function (err) {
            if (err) reject(err);
            else resolve(this);
        });
    });
}

function getAsync(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.get(sql, params, (err, row) => {
            if (err) reject(err);
            else resolve(row);
        });
    });
}

function parseExcelDate(value) {
    if (typeof value === "number") {
        const date = new Date(Date.UTC(1899, 11, 30));
        date.setUTCDate(date.getUTCDate() + value);
        return date.toISOString().slice(0, 10);
    }

    if (typeof value === "string") {
        const parts = value.trim().split(".");
        if (parts.length === 3) {
            const day = Number(parts[0]);
            const month = Number(parts[1]);
            const year = Number(parts[2]);

            const date = new Date(Date.UTC(year, month - 1, day));
            if (
                date.getUTCFullYear() === year &&
                date.getUTCMonth() === month - 1 &&
                date.getUTCDate() === day
            ) {
                return date.toISOString().slice(0, 10);
            }

            if (month === 2 && day > 28) {
                return `${year}-02-28`;
            }
        }
    }

    return "2025-01-01";
}

function clean(value) {
    if (value === null || value === undefined) return null;
    return String(value).trim();
}

async function getId(table, column, value) {
    const row = await getAsync(`SELECT id FROM ${table} WHERE ${column} = ?`, [value]);
    return row ? row.id : null;
}

async function insertDictionary(table, column, value) {
    const text = clean(value);
    if (!text) return null;
    await runAsync(`INSERT OR IGNORE INTO ${table}(${column}) VALUES(?)`, [text]);
    return await getId(table, column, text);
}

async function main() {
    await runAsync("PRAGMA foreign_keys = ON");

    const users = readExcel("./import/user_import.xlsx").filter(row => row["Логин"]);
    const products = readExcel("./import/Tovar.xlsx").filter(row => row["Артикул"]);
    const orders = readExcel("./import/Заказ_import.xlsx").filter(row => row["Номер заказа"]);
    const pickupPoints = readExcel("./import/Пункты выдачи_import.xlsx");

    const roles = ["Гость", "Авторизированный клиент", "Менеджер", "Администратор"];
    for (const role of roles) {
        await runAsync("INSERT OR IGNORE INTO roles(role_name) VALUES(?)", [role]);
    }

    for (const point of pickupPoints) {
        const address = clean(Object.values(point)[0]);
        if (address) {
            await runAsync("INSERT OR IGNORE INTO pickup_points(address) VALUES(?)", [address]);
        }
    }

    for (const product of products) {
        await insertDictionary("categories", "category_name", product["Категория товара"]);
        await insertDictionary("suppliers", "supplier_name", product["Поставщик"]);
        await insertDictionary("manufacturers", "manufacturer_name", product["Производитель"]);
    }

    for (const user of users) {
        const roleId = await getId("roles", "role_name", clean(user["Роль сотрудника"]));
        const passwordHash = await bcrypt.hash(String(user["Пароль"]), 10);

        await runAsync(`
            INSERT OR IGNORE INTO users(role_id, full_name, login, password_hash)
            VALUES(?, ?, ?, ?)
        `, [
            roleId,
            clean(user["ФИО"]),
            clean(user["Логин"]),
            passwordHash
        ]);
    }

    for (const product of products) {
        const categoryId = await getId("categories", "category_name", clean(product["Категория товара"]));
        const supplierId = await getId("suppliers", "supplier_name", clean(product["Поставщик"]));
        const manufacturerId = await getId("manufacturers", "manufacturer_name", clean(product["Производитель"]));

        await runAsync(`
            INSERT OR IGNORE INTO products(
                article, name, unit, price, supplier_id, manufacturer_id,
                category_id, discount, stock_quantity, description, photo
            )
            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [
            clean(product["Артикул"]),
            clean(product["Наименование товара"]),
            clean(product["Единица измерения"]),
            Number(product["Цена"]) || 0,
            supplierId,
            manufacturerId,
            categoryId,
            Number(product["Действующая скидка"]) || 0,
            Number(product["Кол-во на складе"]) || 0,
            clean(product["Описание товара"]),
            clean(product["Фото"])
        ]);
    }

    for (const order of orders) {
        const user = await getAsync(
            "SELECT id FROM users WHERE full_name = ? ORDER BY id LIMIT 1",
            [clean(order["ФИО авторизированного клиента"])]
        );

        const orderId = Number(order["Номер заказа"]);
        const pickupPointId = Number(order["Адрес пункта выдачи"]) || null;
        const orderDate = parseExcelDate(order["Дата заказа"]);
        const status = clean(order["Статус заказа"]) || "Новый";

        await runAsync(`
            INSERT OR IGNORE INTO orders(id, user_id, pickup_point_id, order_date, status)
            VALUES(?, ?, ?, ?, ?)
        `, [
            orderId,
            user ? user.id : null,
            pickupPointId,
            orderDate,
            status
        ]);

        const parts = String(order["Артикул заказа"]).split(",").map(item => item.trim());
        for (let i = 0; i < parts.length; i += 2) {
            const article = parts[i];
            const quantity = Number(parts[i + 1]) || 1;
            const product = await getAsync(
                "SELECT id, price FROM products WHERE article = ?",
                [article]
            );

            if (product) {
                await runAsync(`
                    INSERT INTO order_items(order_id, product_id, quantity, price)
                    VALUES(?, ?, ?, ?)
                `, [
                    orderId,
                    product.id,
                    quantity,
                    product.price
                ]);
            }
        }
    }

    console.log("Импорт завершён");
    console.log(`Товары: ${products.length}`);
    console.log(`Пользователи: ${users.length}`);
    console.log(`Заказы: ${orders.length}`);
}

main()
    .catch(error => {
        console.error("Ошибка импорта:", error);
    })
    .finally(() => {
        db.close();
    });
