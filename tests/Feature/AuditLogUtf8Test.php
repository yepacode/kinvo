<?php
namespace Tests\Feature;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresión: si un input tenía UTF-8 malformado (encoding Windows-1252 típico)
 * AuditLog::record cascaba con JsonEncodingException y la request completa a 500.
 * Descubierto durante auditoría ago-2026 al crear ofertas con caracteres especiales.
 */
class AuditLogUtf8Test extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_no_revienta_con_utf8_malformado(): void
    {
        $u = User::factory()->create();
        // Simular string mal encodeado (viene como ISO-8859-1)
        $malo = mb_convert_encoding('Café D\'Amico ñ', 'ISO-8859-1', 'UTF-8');
        $log = AuditLog::record($u, $u, 'test_utf', new: ['nombre' => $malo]);
        $this->assertNotNull($log);
        // Debe haberse convertido a UTF-8 válido y preservado los acentos
        $this->assertTrue(mb_check_encoding($log->new['nombre'], 'UTF-8'));
        $this->assertStringContainsString('Café', $log->new['nombre']);
    }

    public function test_audit_log_maneja_arrays_anidados(): void
    {
        $u = User::factory()->create();
        $malo = mb_convert_encoding('píúñö', 'ISO-8859-1', 'UTF-8');
        $log = AuditLog::record($u, $u, 'test_nested', new: [
            'nivel1' => ['nivel2' => ['nombre' => $malo]],
        ]);
        $this->assertTrue(mb_check_encoding($log->new['nivel1']['nivel2']['nombre'], 'UTF-8'));
    }
}
