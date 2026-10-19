<?php

namespace App\Exceptions;

/**
 * El aviso que llegó a /webhooks/billing no viene de la pasarela.
 *
 * Se distingue del resto de fallos a propósito. Antes cualquier error dentro
 * de `verifyWebhook` —incluido uno al consultar la API de MercadoPago, que
 * ocurre DESPUÉS de haber validado la firma— se contestaba con un
 * "Invalid signature" y un 400. Dos consecuencias feas:
 *
 *  - al revisar por qué no se confirmaban los pagos, parecía que la clave
 *    del webhook estaba mal, cuando era correcta;
 *  - MercadoPago toma el 400 como entrega fallida y reintenta el aviso una y
 *    otra vez, hasta llegar a desactivar el webhook por sí solo.
 *
 * Sólo las fallas de autenticidad usan esta excepción y devuelven 400.
 */
class FirmaDeWebhookInvalida extends \RuntimeException
{
}
