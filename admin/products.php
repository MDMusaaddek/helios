<?php

session_start();

if (empty($_SESSION['helios_admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';


/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['helios_csrf_token'])) {
    $_SESSION['helios_csrf_token'] = bin2hex(random_bytes(32));
}


/* =========================================
   VARIABLES
========================================= */

$message = '';
$error = '';


function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================
   PRODUCT IMAGE DIRECTORY
========================================= */

$uploadDir = __DIR__ . '/../assets/images/products/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


/* =========================================
   HANDLE FORM SUBMISSIONS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals(
        $_SESSION['helios_csrf_token'],
        $token
    )) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';


        /* =========================================
           ADD PRODUCT
        ========================================= */

        if ($action === 'add') {

            $name = trim($_POST['name'] ?? '');

            $category = $_POST['category'] ?? '';

            $price = filter_var(
                $_POST['price'] ?? '',
                FILTER_VALIDATE_FLOAT
            );

            $oldPriceInput = trim(
                $_POST['old_price'] ?? ''
            );

            $description = trim(
                $_POST['short_description'] ?? ''
            );

            $status = $_POST['status'] ?? 'Active';


            /*
            -----------------------------------------
            NEW SIZE + COLOR VALUES
            -----------------------------------------
            Example:

            Sizes:
            M, L, XL, XXL

            Colors:
            Black, White, Navy, Brown
            */

            $availableSizes = trim(
                $_POST['available_sizes'] ?? ''
            );

            $availableColors = trim(
                $_POST['available_colors'] ?? ''
            );


            $oldPrice = null;

            if ($oldPriceInput !== '') {

                $oldPrice = filter_var(
                    $oldPriceInput,
                    FILTER_VALIDATE_FLOAT
                );

            }


            /* =========================================
               VALIDATION
            ========================================= */

            if (
                $name === '' ||

                !in_array(
                    $category,
                    ['Men', 'Women'],
                    true
                ) ||

                $price === false ||

                $price <= 0 ||

                (
                    $oldPriceInput !== '' &&
                    (
                        $oldPrice === false ||
                        $oldPrice <= 0
                    )
                ) ||

                !in_array(
                    $status,
                    [
                        'Active',
                        'Featured',
                        'Inactive'
                    ],
                    true
                ) ||

                strlen($availableSizes) > 255 ||

                strlen($availableColors) > 255

            ) {

                $error =
                    'Please enter valid product information.';

            } elseif (
                !isset($_FILES['image']) ||
                $_FILES['image']['error']
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    'Please select a product image.';

            } else {

                $file = $_FILES['image'];


                /* =========================================
                   IMAGE SIZE
                ========================================= */

                if ($file['size'] > 5 * 1024 * 1024) {

                    $error =
                        'Image size must be 5 MB or less.';

                } else {

                    $finfo =
                        new finfo(FILEINFO_MIME_TYPE);

                    $mime =
                        $finfo->file($file['tmp_name']);


                    $allowedTypes = [

                        'image/jpeg' => 'jpg',

                        'image/png' => 'png',

                        'image/webp' => 'webp'

                    ];


                    if (!isset(
                        $allowedTypes[$mime]
                    )) {

                        $error =
                            'Use a JPG, PNG, or WebP image.';

                    } else {


                        /* =========================================
                           CREATE UNIQUE IMAGE NAME
                        ========================================= */

                        $imageName =
                            bin2hex(random_bytes(16))
                            . '.'
                            . $allowedTypes[$mime];


                        $destination =
                            $uploadDir . $imageName;


                        /* =========================================
                           UPLOAD IMAGE
                        ========================================= */

                        if (!move_uploaded_file(
                            $file['tmp_name'],
                            $destination
                        )) {

                            $error =
                                'Image upload failed. Check folder permissions.';

                        } else {


                            /* =========================================
                               INSERT PRODUCT INTO DATABASE

                               INCLUDING:

                               available_sizes
                               available_colors
                            ========================================= */

                            $stmt = $conn->prepare(

                                "INSERT INTO products
                                (
                                    name,
                                    category,
                                    price,
                                    old_price,
                                    short_description,
                                    image,
                                    status,
                                    available_sizes,
                                    available_colors
                                )

                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )"

                            );


                            if (!$stmt) {

                                @unlink($destination);

                                $error =
                                    'Could not prepare the database query.';

                            } else {


                                $stmt->bind_param(

                                    'ssddsssss',

                                    $name,

                                    $category,

                                    $price,

                                    $oldPrice,

                                    $description,

                                    $imageName,

                                    $status,

                                    $availableSizes,

                                    $availableColors

                                );


                                if ($stmt->execute()) {

                                    $stmt->close();

                                    header(
                                        'Location: products.php?added=1'
                                    );

                                    exit;

                                }


                                $stmt->close();

                                @unlink($destination);

                                $error =
                                    'Could not save the product. Check your database columns.';

                            }

                        }

                    }

                }

            }

        }


        /* =========================================
           ACTIVATE / DEACTIVATE PRODUCT
        ========================================= */

        if ($action === 'toggle_status') {

            $id = filter_var(
                $_POST['product_id'] ?? '',
                FILTER_VALIDATE_INT
            );


            if (!$id || $id < 1) {

                $error = 'Invalid product ID.';

            } else {

                $stmt = $conn->prepare(

                    "UPDATE products

                     SET status = CASE

                         WHEN status = 'Inactive'
                         THEN 'Active'

                         ELSE 'Inactive'

                     END

                     WHERE id = ?"

                );


                if ($stmt) {

                    $stmt->bind_param(
                        'i',
                        $id
                    );


                    if (
                        $stmt->execute() &&
                        $stmt->affected_rows > 0
                    ) {

                        $stmt->close();

                        header(
                            'Location: products.php?status_changed=1'
                        );

                        exit;

                    }


                    $stmt->close();

                    $error =
                        'Product was not found or its status could not be changed.';

                } else {

                    $error =
                        'Could not prepare the status update.';

                }

            }

        }


        /* =========================================
           DELETE PRODUCT
        ========================================= */

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['product_id'] ?? '',
                FILTER_VALIDATE_INT
            );


            if (!$id || $id < 1) {

                $error =
                    'Invalid product ID.';

            } else {

                $stmt = $conn->prepare(

                    "DELETE FROM products
                     WHERE id = ?"

                );


                if ($stmt) {

                    $stmt->bind_param(
                        'i',
                        $id
                    );


                    if (
                        $stmt->execute() &&
                        $stmt->affected_rows > 0
                    ) {

                        $stmt->close();

                        header(
                            'Location: products.php?deleted=1'
                        );

                        exit;

                    }


                    $stmt->close();

                    $error =
                        'Product was not found or could not be deleted.';

                } else {

                    $error =
                        'Could not prepare the delete query.';

                }

            }

        }

    }

}


/* =========================================
   SUCCESS MESSAGES
========================================= */

if (isset($_GET['added'])) {

    $message =
        'Product added successfully!';

} elseif (isset($_GET['deleted'])) {

    $message =
        'Product deleted successfully.';

} elseif (isset($_GET['updated'])) {

    $message =
        'Product updated successfully!';

} elseif (isset($_GET['status_changed'])) {

    $message =
        'Product status updated successfully!';

}


/* =========================================
   LOAD PRODUCTS
========================================= */

$products = [];


$result = $conn->query(

    "SELECT

        id,
        name,
        category,
        price,
        old_price,
        short_description,
        image,
        status,
        available_sizes,
        available_colors

     FROM products

     ORDER BY id DESC"

);


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $products[] = $row;

    }

}


/* =========================================
   PRODUCT COUNTS
========================================= */

$totalProducts =
    count($products);

$activeProducts = 0;

$featuredProducts = 0;

$inactiveProducts = 0;


foreach ($products as $item) {

    if ($item['status'] === 'Active') {

        $activeProducts++;

    } elseif (
        $item['status'] === 'Featured'
    ) {

        $featuredProducts++;

    } elseif (
        $item['status'] === 'Inactive'
    ) {

        $inactiveProducts++;

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Manage Products | HELIOS
</title>


<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


body {

    font-family:
        Arial,
        sans-serif;

    background:
        #f7f1e8;

    color:
        #302017;

    line-height:
        1.6;

}


/* =========================================
   HEADER
========================================= */

header {

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 15px;

    padding:
        18px 5%;

    background:
        #fffdf9;

    border-bottom:
        1px solid #e8ded2;

}


.logo {

    font-family:
        Georgia,
        serif;

    font-size:
        27px;

    font-weight:
        bold;

    letter-spacing:
        3px;

    color:
        #4b3024;

}


header a {

    color:
        #4b3024;

    text-decoration:
        none;

    font-size:
        13px;

}


/* =========================================
   MAIN
========================================= */

main {

    max-width:
        1200px;

    margin:
        35px auto;

    padding:
        0 20px;

}


h1,
h2 {

    font-family:
        Georgia,
        serif;

    font-weight:
        normal;

}


h1 {

    margin-bottom:
        6px;

    font-size:
        34px;

}


.intro {

    margin-bottom:
        28px;

    color:
        #71645b;

}


/* =========================================
   PANELS
========================================= */

.panel {

    margin-bottom:
        30px;

    padding:
        26px;

    background:
        #fffdf9;

    border:
        1px solid #e8ded2;

}


.panel h2 {

    margin-bottom:
        20px;

    font-size:
        25px;

}


/* =========================================
   FORM
========================================= */

.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap:
        18px;

}


.field {

    min-width:
        0;

}


.full-width {

    grid-column:
        1 / -1;

}


label {

    display:
        block;

    margin-bottom:
        7px;

    font-size:
        13px;

    font-weight:
        bold;

}


input,
select,
textarea {

    width:
        100%;

    padding:
        12px;

    border:
        1px solid #d9cbbd;

    background:
        #fff;

    color:
        #302017;

    font:
        inherit;

    font-size:
        14px;

}


textarea {

    min-height:
        90px;

    resize:
        vertical;

}


input:focus,
select:focus,
textarea:focus {

    outline:
        1px solid #9a734b;

    border-color:
        #9a734b;

}


.field small {

    display:
        block;

    margin-top:
        6px;

    color:
        #71645b;

    font-size:
        12px;

    line-height:
        1.4;

}


/* =========================================
   BUTTONS
========================================= */

button {

    display:
        inline-block;

    padding:
        12px 18px;

    border:
        0;

    background:
        #4b3024;

    color:
        white;

    font-size:
        12px;

    font-weight:
        bold;

    cursor:
        pointer;

}


button:hover {

    background:
        #9a734b;

}


/* =========================================
   MESSAGE
========================================= */

.message {

    margin-bottom:
        20px;

    padding:
        13px;

    background:
        #e8f4e8;

    color:
        #245a2a;

}


.error {

    margin-bottom:
        20px;

    padding:
        13px;

    background:
        #fbe9e7;

    color:
        #a12a20;

}


/* =========================================
   TABLE
========================================= */

.table-wrap {

    width:
        100%;

    overflow-x:
        auto;

}


table {

    width:
        100%;

    min-width:
        900px;

    border-collapse:
        collapse;

    background:
        white;

}


th,
td {

    padding:
        13px 12px;

    border-bottom:
        1px solid #eee5da;

    text-align:
        left;

    vertical-align:
        middle;

    font-size:
        13px;

}


th {

    background:
        #eee5da;

    font-size:
        12px;

}


.thumb {

    width:
        65px;

    height:
        80px;

    object-fit:
        cover;

    background:
        #eee5da;

}


.status {

    display:
        inline-block;

    padding:
        4px 8px;

    background:
        #eee5da;

    font-size:
        11px;

}


.product-options {

    margin-top:
        5px;

    color:
        #71645b;

    font-size:
        11px;

}


.edit-btn {

    display:
        inline-block;

    margin-bottom:
        6px;

    padding:
        10px 14px;

    background:
        #4b3024;

    color:
        #fff;

    font-size:
        12px;

    text-decoration:
        none;

}


.edit-btn:hover {

    background:
        #9a734b;

}


.delete-btn {

    background:
        #963d32;

}


.delete-btn:hover {

    background:
        #71291f;

}


/* =========================================
   PRODUCT SUMMARY CARDS
========================================= */

.stats-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap:
        14px;

    margin:
        22px 0 30px;

}


.stat-card {

    padding:
        18px;

    background:
        #fffdf9;

    border:
        1px solid #e8ded2;

}


.stat-card span {

    display:
        block;

    color:
        #71645b;

    font-size:
        12px;

    margin-bottom:
        5px;

}


.stat-card strong {

    font-family:
        Georgia,
        serif;

    font-size:
        28px;

    font-weight:
        normal;

    color:
        #4b3024;

}


/* =========================================
   SEARCH FILTER
========================================= */

.filter-bar {

    display:
        grid;

    grid-template-columns:
        minmax(220px, 2fr)
        repeat(
            2,
            minmax(150px, 1fr)
        );

    gap:
        12px;

    margin-bottom:
        18px;

}


.filter-bar input,
.filter-bar select {

    width:
        100%;

    min-width:
        0;

    padding:
        12px;

    border:
        1px solid #d9cbbd;

    background:
        #fff;

    color:
        #302017;

    font:
        inherit;

    font-size:
        14px;

}


.results-count {

    margin-bottom:
        12px;

    color:
        #71645b;

    font-size:
        13px;

}


.empty {

    padding:
        25px;

    color:
        #71645b;

    text-align:
        center;

}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 600px) {

    header {

        padding:
            15px 4%;

    }


    .logo {

        font-size:
            22px;

    }


    main {

        margin:
            25px auto;

        padding:
            0 12px;

    }


    h1 {

        font-size:
            29px;

    }


    .panel {

        padding:
            17px;

    }


    .form-grid {

        grid-template-columns:
            1fr;

    }


    .full-width {

        grid-column:
            auto;

    }


    .stats-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );

        gap:
            10px;

    }


    .stat-card {

        padding:
            14px;

    }


    .stat-card strong {

        font-size:
            24px;

    }


    .filter-bar {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header>

    <div class="logo">
        HELIOS
    </div>


    <nav>

        <a href="index.php">
            Dashboard
        </a>

        &nbsp; | &nbsp;

        <a
            href="/helios/"
            target="_blank"
        >
            View Store
        </a>

        &nbsp; | &nbsp;

        <a href="logout.php">
            Log Out
        </a>

    </nav>

</header>


<main>


<h1>
Product Management
</h1>


<p class="intro">

    Add and manage the products displayed
    in your HELIOS store.

</p>



<!-- =========================================
     PRODUCT COUNTS
========================================= -->

<div class="stats-grid">


    <div class="stat-card">

        <span>
            Total Products
        </span>

        <strong>
            <?= $totalProducts ?>
        </strong>

    </div>


    <div class="stat-card">

        <span>
            Active
        </span>

        <strong>
            <?= $activeProducts ?>
        </strong>

    </div>


    <div class="stat-card">

        <span>
            Featured
        </span>

        <strong>
            <?= $featuredProducts ?>
        </strong>

    </div>


    <div class="stat-card">

        <span>
            Inactive
        </span>

        <strong>
            <?= $inactiveProducts ?>
        </strong>

    </div>


</div>



<!-- =========================================
     MESSAGES
========================================= -->

<?php if ($message !== ''): ?>

<div class="message">

    <?= h($message) ?>

</div>

<?php endif; ?>


<?php if ($error !== ''): ?>

<div class="error">

    <?= h($error) ?>

</div>

<?php endif; ?>



<!-- =========================================
     ADD NEW PRODUCT
========================================= -->

<section class="panel">


<h2>
Add New Product
</h2>


<form
    method="POST"
    enctype="multipart/form-data"
>


<input
    type="hidden"
    name="csrf_token"
    value="<?= h(
        $_SESSION['helios_csrf_token']
    ) ?>"
>


<input
    type="hidden"
    name="action"
    value="add"
>


<div class="form-grid">


<!-- PRODUCT NAME -->

<div class="field">

    <label for="name">
        Product Name *
    </label>

    <input
        type="text"
        id="name"
        name="name"
        maxlength="200"
        required
    >

</div>



<!-- CATEGORY -->

<div class="field">

    <label for="category">
        Category *
    </label>

    <select
        id="category"
        name="category"
        required
    >

        <option value="Men">
            Men
        </option>

        <option value="Women">
            Women
        </option>

    </select>

</div>



<!-- PRICE -->

<div class="field">

    <label for="price">
        Selling Price (৳) *
    </label>

    <input
        type="number"
        id="price"
        name="price"
        min="0.01"
        step="0.01"
        required
    >

</div>



<!-- OLD PRICE -->

<div class="field">

    <label for="old_price">
        Previous Price (৳)
    </label>

    <input
        type="number"
        id="old_price"
        name="old_price"
        min="0.01"
        step="0.01"
    >

</div>



<!-- =========================================
     AVAILABLE SIZES
========================================= -->

<div class="field">

    <label for="available_sizes">
        Available Sizes
    </label>

    <input
        type="text"
        id="available_sizes"
        name="available_sizes"
        maxlength="255"
        placeholder="Example: M, L, XL, XXL"
    >

    <small>
        Add multiple sizes separated by commas.
        Example: M, L, XL, XXL
    </small>

</div>



<!-- =========================================
     AVAILABLE COLORS
========================================= -->

<div class="field">

    <label for="available_colors">
        Available Colors
    </label>

    <input
        type="text"
        id="available_colors"
        name="available_colors"
        maxlength="255"
        placeholder="Example: Black, White, Navy, Brown"
    >

    <small>
        Add multiple colors separated by commas.
        Example: Black, White, Navy, Brown
    </small>

</div>



<!-- STATUS -->

<div class="field">

    <label for="status">
        Product Status *
    </label>

    <select
        id="status"
        name="status"
        required
    >

        <option value="Active">
            Active
        </option>

        <option value="Featured">
            Featured
        </option>

        <option value="Inactive">
            Inactive
        </option>

    </select>

</div>



<!-- IMAGE -->

<div class="field">

    <label for="image">

        Product Image *
        (JPG, PNG, WebP; max 5 MB)

    </label>

    <input
        type="file"
        id="image"
        name="image"
        accept=".jpg,.jpeg,.png,.webp"
        required
    >

</div>



<!-- DESCRIPTION -->

<div class="field full-width">

    <label for="short_description">
        Short Description
    </label>

    <textarea
        id="short_description"
        name="short_description"
        maxlength="1000"
        placeholder="Write a short product description..."
    ></textarea>

</div>



<!-- ADD BUTTON -->

<div class="field full-width">

    <button type="submit">
        ADD PRODUCT
    </button>

</div>


</div>

</form>


</section>



<!-- =========================================
     PRODUCT LIST
========================================= -->

<section class="panel">


<h2>
All Products (<?= count($products) ?>)
</h2>


<?php if (count($products) === 0): ?>


<p class="empty">

    No products have been added yet.

</p>


<?php else: ?>



<!-- =========================================
     SEARCH + FILTER
========================================= -->

<div class="filter-bar">


<input
    type="search"
    id="productSearch"
    placeholder="Search by product name..."
    aria-label="Search products by name"
>


<select
    id="categoryFilter"
    aria-label="Filter by category"
>

    <option value="">
        All Categories
    </option>

    <option value="Men">
        Men
    </option>

    <option value="Women">
        Women
    </option>

</select>


<select
    id="statusFilter"
    aria-label="Filter by status"
>

    <option value="">
        All Statuses
    </option>

    <option value="Active">
        Active
    </option>

    <option value="Featured">
        Featured
    </option>

    <option value="Inactive">
        Inactive
    </option>

</select>


</div>


<p
    class="results-count"
    id="resultsCount"
>

Showing
<?= count($products) ?>

of

<?= count($products) ?>

products

</p>



<!-- =========================================
     PRODUCT TABLE
========================================= -->

<div class="table-wrap">


<table id="productsTable">


<thead>

<tr>

    <th>Image</th>

    <th>Product</th>

    <th>Category</th>

    <th>Price</th>

    <th>Status</th>

    <th>Action</th>

</tr>

</thead>


<tbody>


<?php foreach ($products as $product): ?>


<tr

    data-name="<?= h(
        $product['name']
    ) ?>"

    data-category="<?= h(
        $product['category']
    ) ?>"

    data-status="<?= h(
        $product['status']
    ) ?>"

>


<!-- IMAGE -->

<td>

<img

    class="thumb"

    src="/helios/assets/images/products/<?= rawurlencode(
        basename($product['image'])
    ) ?>"

    alt="<?= h(
        $product['name']
    ) ?>"

>

</td>



<!-- PRODUCT INFO -->

<td>


<strong>

<?= h(
    $product['name']
) ?>

</strong>


<br>


<small>

<?= h(
    $product['short_description']
) ?>

</small>


<?php if (
    !empty($product['available_sizes'])
): ?>

<div class="product-options">

    <strong>Sizes:</strong>

    <?= h(
        $product['available_sizes']
    ) ?>

</div>

<?php endif; ?>


<?php if (
    !empty($product['available_colors'])
): ?>

<div class="product-options">

    <strong>Colors:</strong>

    <?= h(
        $product['available_colors']
    ) ?>

</div>

<?php endif; ?>


</td>



<!-- CATEGORY -->

<td>

<?= h(
    $product['category']
) ?>

</td>



<!-- PRICE -->

<td>

৳<?= number_format(
    (float)$product['price'],
    2
) ?>

</td>



<!-- STATUS -->

<td>

<span class="status">

<?= h(
    $product['status']
) ?>

</span>

</td>



<!-- ACTION -->

<td>


<!-- EDIT -->

<a

    href="edit-product.php?id=<?= (int)$product['id'] ?>"

    class="edit-btn"

>

EDIT

</a>



<!-- ACTIVATE / DEACTIVATE -->

<form

    method="POST"

    onsubmit="
        return confirm(
            'Change this product status?'
        );
    "

    style="margin-bottom:6px;"

>


<input

    type="hidden"

    name="csrf_token"

    value="<?= h(
        $_SESSION['helios_csrf_token']
    ) ?>"

>


<input

    type="hidden"

    name="action"

    value="toggle_status"

>


<input

    type="hidden"

    name="product_id"

    value="<?= (int)$product['id'] ?>"

>


<button

    type="submit"

    style="
        background:
        <?= $product['status'] === 'Inactive'
            ? '#357548'
            : '#9a734b'
        ?>;
    "

>

<?= $product['status'] === 'Inactive'
    ? 'ACTIVATE'
    : 'DEACTIVATE'
?>

</button>


</form>



<!-- DELETE -->

<form

    method="POST"

    onsubmit="
        return confirm(
            'Delete this product permanently?'
        );
    "

>


<input

    type="hidden"

    name="csrf_token"

    value="<?= h(
        $_SESSION['helios_csrf_token']
    ) ?>"

>


<input

    type="hidden"

    name="action"

    value="delete"

>


<input

    type="hidden"

    name="product_id"

    value="<?= (int)$product['id'] ?>"

>


<button

    type="submit"

    class="delete-btn"

>

DELETE

</button>


</form>


</td>


</tr>


<?php endforeach; ?>



<!-- NO FILTER RESULTS -->

<tr
    id="noFilterResults"
    style="display:none;"
>

<td
    colspan="6"
    class="empty"
>

No products match your search or filters.

</td>

</tr>


</tbody>


</table>


</div>


<?php endif; ?>


</section>


</main>



<!-- =========================================
     PRODUCT SEARCH + FILTER JAVASCRIPT
========================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const searchInput =
            document.getElementById(
                'productSearch'
            );


        const categoryFilter =
            document.getElementById(
                'categoryFilter'
            );


        const statusFilter =
            document.getElementById(
                'statusFilter'
            );


        const table =
            document.getElementById(
                'productsTable'
            );


        const resultsCount =
            document.getElementById(
                'resultsCount'
            );


        const noResultsRow =
            document.getElementById(
                'noFilterResults'
            );


        if (
            !searchInput ||
            !categoryFilter ||
            !statusFilter ||
            !table
        ) {

            return;

        }


        const productRows =
            Array.from(

                table.querySelectorAll(
                    'tbody tr[data-name]'
                )

            );


        function filterProducts() {


            const searchTerm =
                searchInput
                .value
                .trim()
                .toLowerCase();


            const selectedCategory =
                categoryFilter.value;


            const selectedStatus =
                statusFilter.value;


            let visibleCount = 0;


            productRows.forEach(
                function (row) {


                    const name =
                        (
                            row.dataset.name ||
                            ''
                        )
                        .toLowerCase();


                    const category =
                        row.dataset.category ||
                        '';


                    const status =
                        row.dataset.status ||
                        '';


                    const matchesName =
                        name.includes(
                            searchTerm
                        );


                    const matchesCategory =
                        !selectedCategory ||
                        category ===
                        selectedCategory;


                    const matchesStatus =
                        !selectedStatus ||
                        status ===
                        selectedStatus;


                    const showRow =
                        matchesName &&
                        matchesCategory &&
                        matchesStatus;


                    row.style.display =
                        showRow
                        ? ''
                        : 'none';


                    if (showRow) {

                        visibleCount++;

                    }


                }
            );


            resultsCount.textContent =

                'Showing ' +
                visibleCount +
                ' of ' +
                productRows.length +
                ' products';


            noResultsRow.style.display =

                visibleCount === 0
                ? ''
                : 'none';

        }


        searchInput.addEventListener(
            'input',
            filterProducts
        );


        categoryFilter.addEventListener(
            'change',
            filterProducts
        );


        statusFilter.addEventListener(
            'change',
            filterProducts
        );


    }
);

</script>


</body>

</html>