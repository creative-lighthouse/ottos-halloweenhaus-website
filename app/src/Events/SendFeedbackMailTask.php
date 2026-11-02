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
 * registrations of an event - once per address - and afterwards deletes the
 * personal data of all its registrations. Linked from the Event edit form.
 */
class SendFeedbackMailTask extends BuildTask
{
    protected static string $commandName = "send-feedback-mail";

    protected string $title = "Feedback-Mail senden & personenbezogene Daten löschen";

    protected static string $description = "Sendet die Feedback-Mail an alle eingecheckten Registrierungen eines Events"
        . " und löscht anschließend Name und E-Mail aller Registrierungen dieses Events.";

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $event = Event::get()->byID((int) $input->getOption("event"));
        if (!$event) {
            $output->writeln("Event nicht gefunden.");
            return Command::FAILURE;
        }

        $output->writeln("Event: " . $event->Title . " (" . $event->DateFormatted . ")");

        if (!SiteConfig::current_site_config()->EmailsActive) {
            $output->writeln("E-Mails sind in den Einstellungen deaktiviert. Es wurde nichts versendet.");
            return Command::FAILURE;
        }

        Environment::increaseTimeLimitTo();
        $result = $event->sendFeedbackMails();
        $output->writeln($result["sent"] . " Feedback-Mail(s) versendet.");

        if ($result["failed"]) {
            $output->writeln(count($result["failed"]) . " fehlgeschlagen: " . implode(", ", $result["failed"]));
            $output->writeln("Die personenbezogenen Daten wurden daher noch NICHT gelöscht."
                . " Erneut ausführen, um nur die fehlgeschlagenen Adressen nochmals zu versuchen.");
            return Command::FAILURE;
        }

        $event->deletePersonalData();
        $output->writeln("Die personenbezogenen Daten der Registrierungen wurden gelöscht.");

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption("event", null, InputOption::VALUE_REQUIRED, "ID des Events"),
        ];
    }
}
