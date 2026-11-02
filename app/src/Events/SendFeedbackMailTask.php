<?php

namespace App\Events;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\Environment;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Sends the thank-you mail (linking to the feedback page) to all checked-in
 * registrations of one or more events - once per address, even across events -
 * and afterwards deletes the personal data of all their registrations.
 * Linked from the Event edit form; for several events at once use the bulk
 * action in the event list (SendFeedbackMailBulkHandler) or ?event=1,2,3.
 */
class SendFeedbackMailTask extends BuildTask
{
    protected static string $commandName = "send-feedback-mail";

    protected string $title = "Feedback-Mail senden & personenbezogene Daten löschen";

    protected static string $description = "Sendet die Feedback-Mail an alle eingecheckten Registrierungen eines oder mehrerer Events"
        . " (jede Adresse nur einmal) und löscht anschließend Name und E-Mail aller Registrierungen dieser Events.";

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $ids = array_filter(array_map("intval", explode(",", (string) $input->getOption("event"))));
        $events = Event::get()->filter("ID", $ids ?: [0])->toArray();
        if (!$events) {
            $output->writeln("Event nicht gefunden.");
            return Command::FAILURE;
        }

        foreach ($events as $event) {
            $output->writeln("Event: " . $event->Title . " (" . $event->DateFormatted . ")");
        }

        if (!SiteConfig::current_site_config()->EmailsActive) {
            $output->writeln("E-Mails sind in den Einstellungen deaktiviert. Es wurde nichts versendet.");
            return Command::FAILURE;
        }

        Environment::increaseTimeLimitTo();
        $result = Event::runFeedbackMail($events);
        foreach (self::resultMessages($result) as $line) {
            $output->writeln($line);
        }

        return $result["failed"] ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param array{sent: int, failed: string[]} $result
     * @return string[]
     */
    public static function resultMessages(array $result): array
    {
        $lines = [$result["sent"] . " Feedback-Mail(s) versendet."];
        if ($result["failed"]) {
            $lines[] = count($result["failed"]) . " fehlgeschlagen: " . implode(", ", $result["failed"]);
            $lines[] = "Die personenbezogenen Daten wurden daher noch NICHT gelöscht."
                . " Erneut ausführen, um nur die fehlgeschlagenen Adressen nochmals zu versuchen.";
        } else {
            $lines[] = "Die personenbezogenen Daten der Registrierungen wurden gelöscht.";
        }
        return $lines;
    }

    public function getOptions(): array
    {
        return [
            new InputOption("event", null, InputOption::VALUE_REQUIRED, "ID des Events, mehrere durch Komma getrennt (z.B. 1,2,3)"),
        ];
    }
}
