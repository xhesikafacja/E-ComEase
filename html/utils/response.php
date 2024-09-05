<?php

function respondWithJson($status, $message) {
    echo json_encode(array(
        'status' => $status,
        'message' => $message
    ));
}