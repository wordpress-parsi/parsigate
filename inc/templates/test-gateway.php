<style>
    body {
        font-family: Tahoma, sans-serif;
        direction: rtl;
        text-align: center;
    }

    h1 {
        color: #333;
        margin-bottom: 20px;
        font-size: 24px;
    }

    p {
        color: #666;
        margin-bottom: 30px;
        font-size: 16px;
        line-height: 1.6;
    }

    .buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }

    .btn {
        display: inline-block;
        width: 48%;
        padding: 15px 20px;
        text-decoration: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        transition: all 0.3s ease;
    }

    .btn-success {
        background: #007cba;
        color: white !important;
    }

    .btn-success:hover {
        background: #005a87;
    }

    .btn-danger {
        background: #dc3232;
        color: white !important;
    }

    .btn-danger:hover {
        background: #a00;
    }
</style>

<div class="container">
    <h1><?php _e("Test Gateway", "parsigate"); ?></h1>
    <p><?php _e("Please click the buttons below to perform the operation", "parsigate"); ?></p>
    <div class="buttons">
        <a href="<?php echo add_query_arg(['status' => 'OK'], $callback_url); ?>" class="btn btn-success">
            <?php _e("Successful Payment", "parsigate"); ?>
        </a>
        <a href="<?php echo add_query_arg(['status' => 'NOK'], $callback_url); ?>" class="btn btn-danger">
            <?php _e("Failed Payment", "parsigate"); ?>
        </a>
    </div>
</div>
