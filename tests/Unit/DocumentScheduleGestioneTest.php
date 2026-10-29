<?php

namespace Tests\Unit;

use App\Models\DocumentSchedule;
use App\Models\Employee;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentScheduleGestioneTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function casi(): array
    {
        return [
            'dipendente' => ['employee', 'approvato', 'UnicoOam', 'Organigramma', 'Segreteria'],
            'produttore' => ['fornitore', 'richiesto', 'UnicoOam', 'Produttori', 'Segreteria rete'],
            'anomalia proforma' => ['fornitore', 'anomalo', 'UnicoCoge', 'Provvigioni', 'Contabile'],
            'pratica' => ['pratica', 'richiesto', 'UnicoFinance', 'Pratiche', 'Istruttore'],
        ];
    }

    #[DataProvider('casi')]
    public function test_gestione_per_tipo(string $tipo, string $stato, string $app, string $menu, string $funzione): void
    {
        $schedule = new DocumentSchedule(['documentable_type' => $tipo, 'status' => $stato]);

        $this->assertSame(['app' => $app, 'menu' => $menu, 'funzione' => $funzione], $schedule->gestione());
    }

    public function test_cliente_non_azienda_va_a_unicofinance(): void
    {
        $schedule = new DocumentSchedule(['documentable_type' => 'client', 'status' => 'richiesto']);
        $schedule->setRelation('documentable', (object) ['is_company' => false]);

        $this->assertSame('UnicoFinance', $schedule->gestione()['app']);
        $this->assertSame('Istruttore', $schedule->gestione()['funzione']);
    }

    public function test_consulente_resta_in_unicooam(): void
    {
        $schedule = new DocumentSchedule(['documentable_type' => 'client', 'status' => 'richiesto']);
        $schedule->setRelation('documentable', (object) ['is_company' => true]);

        $this->assertSame(['app' => 'UnicoOam', 'menu' => 'Consulenti', 'funzione' => 'Segreteria'], $schedule->gestione());
    }

    public function test_funzioni_dai_ruoli_dell_utente(): void
    {
        $user = new User;
        $user->setRelation('employee', new Employee(['employee_roles' => ['dipendente', 'istruttore', 'contabile responsabile']]));

        $this->assertSame(['Contabile', 'Istruttore'], DocumentSchedule::funzioniDelloUtente($user));
        $this->assertSame([], DocumentSchedule::funzioniDelloUtente(null));

        foreach (['admin', 'super_admin'] as $ruolo) {
            $user->role = $ruolo;
            $this->assertSame([], DocumentSchedule::funzioniDelloUtente($user));
        }
    }

    public function test_scope_gestite_da_genera_la_query(): void
    {
        $sql = DocumentSchedule::query()->gestiteDa(['Segreteria', 'Contabile'])->toSql();

        $this->assertStringContainsString('documentable_type', $sql);
    }
}
