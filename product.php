<?php

require_once __DIR__ . '/config/db.php';


function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================
   GET PRODUCT
========================================= */

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$product = null;


if ($id && $id > 0) {

    $stmt = $conn->prepare(

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

         WHERE id = ?

         AND status IN ('Active', 'Featured')

         LIMIT 1"

    );


    if ($stmt) {

        $stmt->bind_param(
            'i',
            $id
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $product =
            $result->fetch_assoc();

        $stmt->close();

    }

}


/* =========================================
   PAGE TITLE
========================================= */

if (!$product) {

    http_response_code(404);

    $pageTitle =
        'Product Not Found | HELIOS';

} else {

    $pageTitle =
        $product['name']
        . ' | HELIOS';

}


/* =========================================
   SIZE + COLOR OPTIONS
========================================= */

$sizes = [];

$colors = [];


if ($product) {

    $sizes = array_values(
        array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    $product['available_sizes'] ?? ''
                )
            )
        )
    );


    $colors = array_values(
        array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    $product['available_colors'] ?? ''
                )
            )
        )
    );


    /*
     * Temporary fallback for older products
     * that do not yet have saved options.
     */

    if (empty($sizes)) {

        $sizes = [
            'M',
            'L',
            'XL',
            'XXL'
        ];

    }


    if (empty($colors)) {

        $colors = [
            'Black',
            'Cream',
            'Brown',
            'Green'
        ];

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
<?= h($pageTitle) ?>
</title>

<meta
    name="description"
    content="<?= $product
        ? h($product['short_description'])
        : 'Explore timeless fashion at HELIOS.'
    ?>"
>


<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


html {

    scroll-behavior: smooth;

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
        1.7;

}


a {

    color:
        inherit;

}


/* =========================================
   HEADER
========================================= */

.site-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    padding:
        20px 7%;

    background:
        #fbf7f1;

    border-bottom:
        1px solid #e3d7c9;

}


.logo {

    font-family:
        Georgia,
        serif;

    font-size:
        29px;

    font-weight:
        bold;

    letter-spacing:
        3px;

    text-decoration:
        none;

    color:
        #4b3024;

}


.header-link {

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        bold;

}


/* =========================================
   BREADCRUMB
========================================= */

.breadcrumb {

    max-width:
        1180px;

    margin:
        25px auto;

    padding:
        0 24px;

    color:
        #76675c;

    font-size:
        13px;

}


.breadcrumb a {

    text-decoration:
        none;

}


/* =========================================
   PRODUCT LAYOUT
========================================= */

.product-layout {

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(0, .9fr);

    align-items:
        start;

    gap:
        65px;

    max-width:
        1180px;

    margin:
        0 auto;

    padding:
        20px 24px 90px;

}


/* =========================================
   PRODUCT IMAGE + ZOOM
========================================= */

.product-photo {

    position:
        relative;

    width:
        100%;

    aspect-ratio:
        4 / 5;

    background:
        #e2d6c8;

    overflow:
        hidden;

    cursor:
        zoom-in;

}


.product-photo img {

    display:
        block;

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    object-position:
        center;

    transform:
        scale(1);

    transform-origin:
        center center;

    transition:
        transform .25s ease;

}


.product-photo.is-zoomed {

    cursor:
        zoom-out;

}


.product-photo.is-zoomed img {

    transform:
        scale(2);

}


.zoom-hint {

    position:
        absolute;

    right:
        12px;

    bottom:
        12px;

    padding:
        8px 12px;

    background:
        rgba(48, 32, 23, .88);

    color:
        #fff;

    font-size:
        10px;

    letter-spacing:
        1px;

    pointer-events:
        none;

}


@media (hover: hover) and (pointer: fine) {

    .product-photo:not(.is-zoomed):hover img {

        transform:
            scale(1.35);

    }

}


/* =========================================
   PRODUCT DETAILS
========================================= */

.product-details {

    padding-top:
        25px;

}


.eyebrow {

    margin-bottom:
        15px;

    color:
        #9a734b;

    font-size:
        12px;

    font-weight:
        bold;

    letter-spacing:
        3px;

}


.product-details h1 {

    margin-bottom:
        18px;

    font-family:
        Georgia,
        serif;

    font-size:
        clamp(
            30px,
            4vw,
            46px
        );

    font-weight:
        normal;

    line-height:
        1.2;

}


/* =========================================
   PRICE
========================================= */

.price-row {

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        14px;

    margin-bottom:
        25px;

}


.current-price {

    font-size:
        25px;

    font-weight:
        bold;

    color:
        #4b3024;

}


.old-price {

    color:
        #93877d;

    font-size:
        17px;

    text-decoration:
        line-through;

}


/* =========================================
   DESCRIPTION
========================================= */

.description {

    padding:
        22px 0;

    border-top:
        1px solid #e3d7c9;

    border-bottom:
        1px solid #e3d7c9;

    color:
        #66584e;

    font-size:
        15px;

}


/* =========================================
   META
========================================= */

.product-meta {

    display:
        grid;

    gap:
        12px;

    margin:
        24px 0;

    font-size:
        14px;

}


.product-meta span {

    color:
        #76675c;

}


/* =========================================
   SIZE + COLOR
========================================= */

.option-group {

    margin:
        24px 0;

}


.option-title {

    display:
        block;

    margin-bottom:
        12px;

    font-size:
        14px;

    font-weight:
        bold;

}


.option-list {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        10px;

}


.option-input {

    position:
        absolute;

    width:
        1px;

    height:
        1px;

    opacity:
        0;

}


.option-label {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    min-width:
        45px;

    min-height:
        42px;

    padding:
        8px 15px;

    border:
        1px solid #d9cbbd;

    background:
        #fffdf9;

    color:
        #4b3024;

    font-size:
        13px;

    cursor:
        pointer;

    transition:
        .2s ease;

}


.option-label:hover {

    border-color:
        #9a734b;

}


.option-input:checked
+
.option-label {

    background:
        #4b3024;

    color:
        #fff;

    border-color:
        #4b3024;

}


.option-input:focus-visible
+
.option-label {

    outline:
        2px solid #9a734b;

    outline-offset:
        3px;

}


/* =========================================
   QUANTITY
========================================= */

.quantity-group {

    margin:
        24px 0;

}


.quantity-title {

    display:
        block;

    margin-bottom:
        12px;

    font-size:
        14px;

    font-weight:
        bold;

}


.quantity-selector {

    display:
        inline-flex;

    align-items:
        center;

    border:
        1px solid #d9cbbd;

    background:
        #fffdf9;

}


.quantity-button {

    width:
        44px;

    height:
        44px;

    border:
        0;

    background:
        transparent;

    color:
        #4b3024;

    font-size:
        22px;

    line-height:
        1;

    cursor:
        pointer;

}


.quantity-button:hover {

    background:
        #eee5da;

}


.quantity-number {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    min-width:
        52px;

    height:
        44px;

    border-left:
        1px solid #d9cbbd;

    border-right:
        1px solid #d9cbbd;

    font-size:
        15px;

    font-weight:
        bold;

}


/* =========================================
   TOTAL PRICE
========================================= */

.total-box {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        15px;

    margin:
        22px 0;

    padding:
        15px 17px;

    background:
        #eee5da;

}


.total-box span {

    color:
        #66584e;

    font-size:
        13px;

}


.total-box strong {

    color:
        #4b3024;

    font-size:
        18px;

}


/* =========================================
   SELECTION MESSAGE
========================================= */

.selection-message {

    margin-top:
        12px;

    color:
        #76675c;

    font-size:
        12px;

}


.selection-error {

    margin:
        12px 0;

    color:
        #a12a20;

    font-size:
        13px;

}


/* =========================================
   ORDER BUTTON
========================================= */

.order-button {

    display:
        block;

    width:
        100%;

    margin-top:
        25px;

    padding:
        16px 20px;

    background:
        #4b3024;

    border:
        1px solid #4b3024;

    color:
        white;

    text-align:
        center;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        bold;

    letter-spacing:
        1.5px;

    transition:
        .3s ease;

}


.order-button:hover {

    background:
        #9a734b;

    border-color:
        #9a734b;

}


.back-link {

    display:
        inline-block;

    margin-top:
        24px;

    color:
        #4b3024;

    font-size:
        13px;

    text-decoration:
        none;

}


/* =========================================
   PRODUCT NOT FOUND
========================================= */

.not-found {

    max-width:
        700px;

    margin:
        100px auto;

    padding:
        30px;

    text-align:
        center;

}


.not-found h1 {

    margin-bottom:
        15px;

    font-family:
        Georgia,
        serif;

    font-size:
        36px;

}


.not-found p {

    margin-bottom:
        25px;

    color:
        #76675c;

}


/* =========================================
   FOOTER
========================================= */

.site-footer {

    padding:
        30px 20px;

    background:
        #291a13;

    color:
        #f7f1e8;

    text-align:
        center;

}


.site-footer h2 {

    margin-bottom:
        8px;

    font-family:
        Georgia,
        serif;

    letter-spacing:
        3px;

}


.site-footer p {

    font-size:
        12px;

    color:
        #d5c3b2;

}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 768px) {

    .site-header {

        padding:
            16px 5%;

    }


    .logo {

        font-size:
            24px;

    }


    .product-layout {

        grid-template-columns:
            1fr;

        gap:
            25px;

        padding:
            10px 5% 55px;

    }


    .product-photo {

        max-height:
            600px;

    }


    .product-details {

        padding-top:
            0;

    }


    .breadcrumb {

        margin:
            18px auto;

        padding:
            0 5%;

    }


    .product-details h1 {

        font-size:
            32px;

    }


    .current-price {

        font-size:
            23px;

    }

}

</style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header class="site-header">

    <a
        href="index.php"
        class="logo"
    >
        HELIOS
    </a>


    <a
        href="index.php#products"
        class="header-link"
    >
        BACK TO SHOP
    </a>

</header>


<?php if ($product): ?>


<!-- =========================================
     BREADCRUMB
========================================= -->

<div class="breadcrumb">

    <a href="index.php">
        Home
    </a>

    &nbsp; / &nbsp;

    <a href="index.php#products">
        Collection
    </a>

    &nbsp; / &nbsp;

    <?= h($product['name']) ?>

</div>



<main class="product-layout">


<!-- =========================================
     PRODUCT IMAGE
========================================= -->

<div
    class="product-photo"
    id="productPhoto"
>

    <img

        id="zoomProductImage"

        src="/helios/assets/images/products/<?= rawurlencode(
            basename($product['image'])
        ) ?>"

        alt="<?= h(
            $product['name']
        ) ?>"

        loading="eager"

    >


    <span
        class="zoom-hint"
        id="zoomHint"
    >

        CLICK TO ZOOM +

    </span>

</div>



<!-- =========================================
     PRODUCT INFORMATION
========================================= -->

<section class="product-details">


<p class="eyebrow">

    HELIOS COLLECTION

</p>


<h1>

    <?= h(
        $product['name']
    ) ?>

</h1>



<!-- =========================================
     PRICE
========================================= -->

<div class="price-row">


<span class="current-price">

    ৳<?= number_format(
        (float)$product['price'],
        0
    ) ?>

</span>


<?php if (
    !empty($product['old_price']) &&
    (float)$product['old_price']
    >
    (float)$product['price']
): ?>


<span class="old-price">

    ৳<?= number_format(
        (float)$product['old_price'],
        0
    ) ?>

</span>


<?php endif; ?>


</div>



<!-- =========================================
     DESCRIPTION
========================================= -->

<p class="description">

<?= nl2br(
    h(
        $product['short_description']
        ?:
        'Discover timeless style and effortless elegance with HELIOS.'
    )
) ?>

</p>



<!-- =========================================
     SIZE
========================================= -->

<div class="option-group">


<span class="option-title">

    Choose your size

</span>


<div class="option-list">


<?php foreach (
    $sizes as $index => $size
): ?>


<div>


<input

    class="option-input"

    type="radio"

    name="size"

    id="size-<?= $index ?>"

    value="<?= h(
        $size
    ) ?>"

>


<label

    class="option-label"

    for="size-<?= $index ?>"

>

<?= h(
    $size
) ?>

</label>


</div>


<?php endforeach; ?>


</div>


</div>



<!-- =========================================
     COLOR
========================================= -->

<div class="option-group">


<span class="option-title">

    Choose your color

</span>


<div class="option-list">


<?php foreach (
    $colors as $index => $color
): ?>


<div>


<input

    class="option-input"

    type="radio"

    name="color"

    id="color-<?= $index ?>"

    value="<?= h(
        $color
    ) ?>"

>


<label

    class="option-label"

    for="color-<?= $index ?>"

>

<?= h(
    $color
) ?>

</label>


</div>


<?php endforeach; ?>


</div>


</div>



<!-- =========================================
     QUANTITY
========================================= -->

<div class="quantity-group">


<span class="quantity-title">

    Quantity

</span>


<div class="quantity-selector">


<button

    type="button"

    class="quantity-button"

    id="quantityMinus"

    aria-label="Decrease quantity"

>

−

</button>


<span

    class="quantity-number"

    id="quantityNumber"

>

1

</span>


<button

    type="button"

    class="quantity-button"

    id="quantityPlus"

    aria-label="Increase quantity"

>

+

</button>


</div>


</div>



<!-- =========================================
     TOTAL
========================================= -->

<div class="total-box">


<span>

    Total

</span>


<strong id="totalPrice">

    ৳<?= number_format(
        (float)$product['price'],
        0
    ) ?>

</strong>


</div>



<!-- =========================================
     SELECTION MESSAGE
========================================= -->

<p

    class="selection-message"

    id="selectionMessage"

>

Please select a size and color before ordering.

</p>


<p

    class="selection-error"

    id="selectionError"

    hidden

>

Please choose both a size and a color.

</p>



<!-- =========================================
     PRODUCT META
========================================= -->

<div class="product-meta">


<div>

    <span>
        Category:
    </span>

    <strong>

        <?= h(
            $product['category']
        ) ?>

    </strong>

</div>


<div>

    <span>
        Availability:
    </span>

    <strong>
        Available to order
    </strong>

</div>


</div>



<!-- =========================================
     WHATSAPP BUTTON
========================================= -->

<a

    href="#"

    id="orderButton"

    class="order-button"

>

SELECT SIZE AND COLOR

</a>



<a

    href="index.php#products"

    class="back-link"

>

&larr; Continue shopping

</a>


</section>


</main>


<?php else: ?>


<!-- =========================================
     PRODUCT NOT FOUND
========================================= -->

<main class="not-found">


<h1>

    Product Not Found

</h1>


<p>

    This product may be unavailable
    or no longer listed.

    Please explore our current collection.

</p>


<a

    href="index.php#products"

    class="order-button"

>

EXPLORE THE COLLECTION

</a>


</main>


<?php endif; ?>



<!-- =========================================
     FOOTER
========================================= -->

<footer class="site-footer">


<h2>

    HELIOS

</h2>


<p>

    Timeless Elegance • Wear Your Light

</p>


<p>

    &copy;
    <?= date('Y') ?>
    HELIOS.
    All rights reserved.

</p>


</footer>



<script>

(function () {


/* =========================================
   IMAGE ZOOM
========================================= */

const photo =
    document.getElementById(
        'productPhoto'
    );


const image =
    document.getElementById(
        'zoomProductImage'
    );


const hint =
    document.getElementById(
        'zoomHint'
    );


if (
    photo &&
    image
) {


    photo.addEventListener(
        'mousemove',
        function (event) {


            if (
                !photo.classList.contains(
                    'is-zoomed'
                )
            ) {

                return;

            }


            const bounds =
                photo.getBoundingClientRect();


            const x =
                (
                    (
                        event.clientX -
                        bounds.left
                    )
                    /
                    bounds.width
                )
                *
                100;


            const y =
                (
                    (
                        event.clientY -
                        bounds.top
                    )
                    /
                    bounds.height
                )
                *
                100;


            image.style.transformOrigin =
                x +
                '% ' +
                y +
                '%';


        }
    );


    photo.addEventListener(
        'click',
        function (event) {


            const bounds =
                photo.getBoundingClientRect();


            const x =
                (
                    (
                        event.clientX -
                        bounds.left
                    )
                    /
                    bounds.width
                )
                *
                100;


            const y =
                (
                    (
                        event.clientY -
                        bounds.top
                    )
                    /
                    bounds.height
                )
                *
                100;


            const zoomed =
                photo.classList.toggle(
                    'is-zoomed'
                );


            if (zoomed) {


                image.style.transformOrigin =
                    x +
                    '% ' +
                    y +
                    '%';


                if (hint) {

                    hint.textContent =
                        'CLICK TO ZOOM OUT −';

                }


            } else {


                image.style.transformOrigin =
                    'center center';


                if (hint) {

                    hint.textContent =
                        'CLICK TO ZOOM +';

                }


            }


        }
    );


}



/* =========================================
   SIZE + COLOR + QUANTITY + WHATSAPP
========================================= */

const orderButton =
    document.getElementById(
        'orderButton'
    );


const error =
    document.getElementById(
        'selectionError'
    );


const message =
    document.getElementById(
        'selectionMessage'
    );


const minusButton =
    document.getElementById(
        'quantityMinus'
    );


const plusButton =
    document.getElementById(
        'quantityPlus'
    );


const quantityNumber =
    document.getElementById(
        'quantityNumber'
    );


const totalPrice =
    document.getElementById(
        'totalPrice'
    );


if (!orderButton) {

    return;

}


/* =========================================
   PRODUCT VALUES
========================================= */

const productName = <?= json_encode(
    $product['name'] ?? ''
) ?>;


const unitPrice = <?= json_encode(
    (float)($product['price'] ?? 0)
) ?>;


const phoneNumber =
    '8801351248758';


let quantity = 1;



/* =========================================
   GET SELECTED OPTION
========================================= */

function getSelected(name) {


    const selected =
        document.querySelector(

            'input[name="' +
            name +
            '"]:checked'

        );


    return selected
        ? selected.value
        : '';

}



/* =========================================
   MONEY FORMAT
========================================= */

function formatMoney(number) {


    return Number(number)
        .toLocaleString(
            'en-US',
            {
                maximumFractionDigits: 0
            }
        );

}



/* =========================================
   UPDATE QUANTITY + TOTAL
========================================= */

function updateQuantity() {


    if (quantity < 1) {

        quantity = 1;

    }


    quantityNumber.textContent =
        quantity;


    const total =
        unitPrice *
        quantity;


    totalPrice.textContent =
        '৳' +
        formatMoney(total);


    updateOrderButton();

}



/* =========================================
   MINUS
========================================= */

if (minusButton) {


    minusButton.addEventListener(
        'click',
        function () {


            if (quantity > 1) {

                quantity--;

                updateQuantity();

            }


        }
    );


}



/* =========================================
   PLUS
========================================= */

if (plusButton) {


    plusButton.addEventListener(
        'click',
        function () {


            quantity++;

            updateQuantity();


        }
    );


}



/* =========================================
   UPDATE ORDER BUTTON
========================================= */

function updateOrderButton() {


    const size =
        getSelected(
            'size'
        );


    const color =
        getSelected(
            'color'
        );


    if (
        size &&
        color
    ) {


        orderButton.textContent =
            'ORDER VIA WHATSAPP';


        message.textContent =

            'Size: ' +
            size +

            ' | Color: ' +
            color +

            ' | Quantity: ' +
            quantity;


        error.hidden =
            true;


    } else {


        orderButton.textContent =
            'SELECT SIZE AND COLOR';


        message.textContent =
            'Please select a size and color before ordering.';


    }


}



/* =========================================
   SIZE + COLOR EVENTS
========================================= */

document.querySelectorAll(

    'input[name="size"], input[name="color"]'

).forEach(

    function (input) {


        input.addEventListener(

            'change',

            updateOrderButton

        );


    }

);



/* =========================================
   WHATSAPP ORDER
========================================= */

orderButton.addEventListener(

    'click',

    function (event) {


        event.preventDefault();


        const size =
            getSelected(
                'size'
            );


        const color =
            getSelected(
                'color'
            );


        if (
            !size ||
            !color
        ) {


            error.hidden =
                false;


            message.textContent =
                'Choose both options to continue.';


            return;

        }


        const total =
            unitPrice *
            quantity;


        const orderMessage =

            'Hello HELIOS! I want to order this product.' +

            '\n\nProduct: ' +
            productName +

            '\nUnit Price: Tk ' +
            formatMoney(unitPrice) +

            '\nSize: ' +
            size +

            '\nColor: ' +
            color +

            '\nQuantity: ' +
            quantity +

            '\nTotal: Tk ' +
            formatMoney(total);


        const url =

            'https://wa.me/' +
            phoneNumber +

            '?text=' +
            encodeURIComponent(
                orderMessage
            );


        window.open(

            url,

            '_blank',

            'noopener,noreferrer'

        );


    }

);


/* INITIAL TOTAL */

updateQuantity();


})();

</script>


</body>

</html>