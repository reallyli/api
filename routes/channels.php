<?php

Broadcast::channel('map-data.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
