<?php

namespace Tests\Unit;

use App\Console\Commands\OpenAuditsFromEmailCommand;
use PHPUnit\Framework\TestCase;

class OpenAuditsPersonalizeTest extends TestCase
{
    private const TEXT = "**Oggetto: verifica collaboratore sig. Graziani Pellegrino – Races Finance Srl.**\n\nEgr. sig. Graziani Pellegrino\n\nSi informa che la funzione di Audit svolgerà una verifica.\n\nLa Segreteria Races";

    public function test_sostituisce_il_nominativo_di_esempio_e_usa_l_oggetto_del_documento(): void
    {
        [$subject, $body] = OpenAuditsFromEmailCommand::personalize(self::TEXT, 'Innero Emilia', 'Verifiche ispettive 1/5 rete');

        $this->assertSame('verifica collaboratore sig. Innero Emilia – Races Finance Srl.', $subject);
        $this->assertStringStartsWith('Egr. sig. Innero Emilia', $body);
        $this->assertStringNotContainsString('Graziani', $subject.$body);
        $this->assertStringNotContainsString('Oggetto:', $body);
    }

    public function test_senza_nominativo_ne_oggetto_il_testo_resta_com_e(): void
    {
        [$subject, $body] = OpenAuditsFromEmailCommand::personalize("Gentile collaboratore,\nsi informa che.", 'Rossi Mario', 'Oggetto dell\'email');

        $this->assertSame('Oggetto dell\'email', $subject);
        $this->assertSame("Gentile collaboratore,\nsi informa che.", $body);
    }

    public function test_nome_visualizzato_con_accento_al_posto_dell_apostrofo(): void
    {
        $this->assertSame('Giofrè Alfonso', OpenAuditsFromEmailCommand::displayName("GIOFRE' ALFONSO"));
        $this->assertSame("D'Angelo Marco", OpenAuditsFromEmailCommand::displayName("D'ANGELO MARCO"));
    }
}
