
<?php
session_start();

if (empty($_SESSION['helios_admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['helios_csrf_token'])) {
    $_SESSION['helios_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    exit('Invalid product ID. Please return to the product list.');
}

/* LOAD THE EXISTING PRODUCT */

$stmt = $conn->prepare(
    "SELECT id, name, category, price, old_price,
            short_description, image, status,
            available_sizes, available_colors
     FROM products
     WHERE id = ?"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

if (!$product) {
    exit('Product not found. Please return to the product list.');
}

/* SET DEFAULT FORM VALUES FROM THE DATABASE */

$name = $product['name'];
$category = $product['category'];
$price = $product['price'];
$oldPrice = $product['old_price'];
$description = $product['short_description'];
$status = $product['status'];
$availableSizes = $product['available_sizes'] ?? '';
$availableColors = $product['available_colors'] ?? '';
$currentImage = $product['image'];

/* PROCESS THE EDIT FORM */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $price = trim($_POST['price'] ?? '');
    $oldPrice = trim($_POST['old_price'] ?? '');
    $description = trim($_POST['short_description'] ?? '');
    $status = $_POST['status'] ?? '';
    $availableSizes = trim($_POST['available_sizes'] ?? '');
    $availableColors = trim($_POST['available_colors'] ?? '');

    if (
        !hash_equals($_SESSION['helios_csrf_token'], $token)
    ) {
        $error = 'Invalid request. Refresh the page and try again.';

    } elseif (
        $name === '' ||
        !in_array($category, ['Men', 'Women'], true) ||
        !is_numeric($price) ||
        (float)$price <= 0 ||
        (
            $oldPrice !== '' &&
            (!is_numeric($oldPrice) || (float)$oldPrice <= 0)
        ) ||
        !in_array($status, ['Active', 'Featured'], true) ||
        strlen($availableSizes) > 255 ||
        strlen($availableColors) > 255
    ) {
        $error = 'Please check the product information and try again.';

    } else {

        $newImage = $currentImage;
        $uploadedPath = null;

        /* IMAGE IS OPTIONAL WHEN EDITING */

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Image upload failed. Please try again.';

            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'The image must be 5 MB or smaller.';

            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['image']['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mime])) {
                    $error = 'Use a JPG, PNG, or WebP image.';

                } else {
                    $uploadDir = __DIR__ .
                        '/../assets/images/products/';

                    if (
                        !is_dir($uploadDir) &&
                        !mkdir($uploadDir, 0755, true)
                    ) {
                        $error = 'Could not create the image folder.';

                    } else {
                        $newImage = bin2hex(random_bytes(16)) .
                            '.' . $allowedTypes[$mime];

                        $uploadedPath = $uploadDir . $newImage;

                        if (!move_uploaded_file(
                            $_FILES['image']['tmp_name'],
                            $uploadedPath
                        )) {
                            $error = 'Could not save the new image.';
                        }
                    }
                }
            }
        }

        /* UPDATE THE EXISTING PRODUCT, NOT INSERT A NEW ONE */

        if ($error === '') {

            $sellingPrice = (float)$price;
            $previousPrice = $oldPrice === ''
                ? null
                : (float)$oldPrice;

            $stmt = $conn->prepare(
                "UPDATE products
                 SET name = ?,
                     category = ?,
                     price = ?,
                     old_price = ?,
                     short_description = ?,
                     image = ?,
                     status = ?,
                     available_sizes = ?,
                     available_colors = ?
                 WHERE id = ?"
            );

            if (!$stmt) {
                $error = 'Could not prepare the database update.';

            } else {
                $stmt->bind_param(
                    'ssddsssssi',
                    $name,
                    $category,
                    $sellingPrice,
                    $previousPrice,
                    $description,
                    $newImage,
                    $status,
                    $availableSizes,
                    $availableColors,
                    $id
                );

                if ($stmt->execute()) {
                    $stmt->close();

                    /* DELETE THE OLD IMAGE ONLY AFTER SAVING */
                    if ($uploadedPath !== null && $currentImage !== '') {
                        $oldPath = __DIR__ .
                            '/../assets/images/products/' .
                            basename($currentImage);

                        if (
                            is_file($oldPath) &&
                            realpath($oldPath) !== realpath($uploadedPath)
                        ) {
                            @unlink($oldPath);
                        }
                    }

                    header('Location: products.php?updated=1');
                    exit;

                } else {
                    $error = 'Could not update the product. Please try again.';
                    $stmt->close();

                    if (
                        $uploadedPath !== null &&
                        is_file($uploadedPath)
                    ) {
                        @unlink($uploadedPath);
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product | HELIOS</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f7f1e8;
            color: #302017;
            line-height: 1.6;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 18px 5%;
            background: #fffdf9;
            border-bottom: 1px solid #e8ded2;
        }

        .logo {
            font-family: Georgia, serif;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #4b3024;
        }

        a {
            color: #4b3024;
            text-decoration: none;
        }

        main {
            max-width: 850px;
            margin: 35px auto;
            padding: 0 18px;
        }

        .panel {
            padding: 28px;
            background: #fffdf9;
            border: 1px solid #e8ded2;
        }

        h1 {
            margin-bottom: 7px;
            font-family: Georgia, serif;
            font-size: 32px;
            font-weight: normal;
        }

        .subtitle {
            margin-bottom: 25px;
            color: #71645b;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 16px;
        }

        .field {
            min-width: 0;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d9cbbd;
            background: white;
            color: #302017;
            font: inherit;
            font-size: 14px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        small {
            display: block;
            margin-top: 6px;
            color: #71645b;
            font-size: 12px;
        }

        .current-image {
            display: block;
            width: 140px;
            height: 170px;
            margin-bottom: 12px;
            object-fit: cover;
            background: #eee5da;
        }

        .error {
            margin-bottom: 20px;
            padding: 12px;
            background: #fbe9e7;
            color: #a12a20;
            font-size: 13px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        button, .cancel {
            display: inline-block;
            padding: 13px 20px;
            border: none;
            background: #4b3024;
            color: white;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #9a734b;
        }

        .cancel {
            background: #e8ded2;
            color: #302017;
        }

        @media (max-width: 600px) {
            .panel {
                padding: 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

            h1 {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="logo">HELIOS</div>
    <a href="products.php">← Back to Products</a>
</header>

<main>
    <section class="panel">

        <h1>Edit Product</h1>

        <p class="subtitle">
            Update the details of your existing HELIOS product.
        </p>

        <?php if ($error !== ''): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= h($_SESSION['helios_csrf_token']) ?>">

            <div class="form-grid">

                <div class="field">
                    <label for="name">Product Name *</label>
                    <input type="text"
                           id="name"
                           name="name"
                           maxlength="200"
                           value="<?= h($name) ?>"
                           required>
                </div>

                <div class="field">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="Men"
                            <?= $category === 'Men' ? 'selected' : '' ?>>
                            Men
                        </option>
                        <option value="Women"
                            <?= $category === 'Women' ? 'selected' : '' ?>>
                            Women
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label for="price">Selling Price (৳) *</label>
                    <input type="number"
                           id="price"
                           name="price"
                           min="0.01"
                           step="0.01"
                           value="<?= h($price) ?>"
                           required>
                </div>

                <div class="field">
                    <label for="old_price">Previous Price (৳)</label>
                    <input type="number"
                           id="old_price"
                           name="old_price"
                           min="0.01"
                           step="0.01"
                           value="<?= h($oldPrice) ?>">
                </div>

                <div class="field full-width">
                    <label for="short_description">
                        Product Description
                    </label>
                    <textarea id="short_description"
                              name="short_description"
                              maxlength="1000"><?= h($description) ?></textarea>
                </div>

                <div class="field">
                    <label for="available_sizes">Available Sizes</label>
                    <input type="text"
                           id="available_sizes"
                           name="available_sizes"
                           maxlength="255"
                           value="<?= h($availableSizes) ?>"
                           placeholder="M, L, XL, XXL">
                    <small>Separate sizes with commas.</small>
                </div>

                <div class="field">
                    <label for="available_colors">Available Colors</label>
                    <input type="text"
                           id="available_colors"
                           name="available_colors"
                           maxlength="255"
                           value="<?= h($availableColors) ?>"
                           placeholder="Black, Cream, Brown">
                    <small>Separate colors with commas.</small>
                </div>

                <div class="field">
                    <label for="status">Product Status *</label>
                    <select id="status" name="status" required>
                        <option value="Active"
                            <?= $status === 'Active' ? 'selected' : '' ?>>
                            Active
                        </option>
                        <option value="Featured"
                            <?= $status === 'Featured' ? 'selected' : '' ?>>
                            Featured
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label for="image">Replace Product Image (optional)</label>
                    <input type="file"
                           id="image"
                           name="image"
                           accept=".jpg,.jpeg,.png,.webp">
                    <small>
                        Leave empty to keep the current image.
                        Maximum 5 MB.
                    </small>
                </div>

                <div class="field full-width">
                    <label>Current Product Image</label>

                    <img
                        class="current-image"
                        src="/helios/assets/images/products/<?= rawurlencode(basename($currentImage)) ?>"
                        alt="<?= h($name) ?>">
                </div>

            </div>

            <div class="actions">
                <button type="submit">SAVE CHANGES</button>
                <a href="products.php" class="cancel">CANCEL</a>
            </div>

        </form>
    </section>
</main>

</body>
</html>
