<?php
/**
 * Template Parser Class
 * 
 * Parses template content and replaces placeholders with actual ticket data
 */

class TemplateParser {
    
    /**
     * Parse template content with ticket data
     * 
     * @param string $content Template content with placeholders
     * @param array $ticket Ticket data
     * @param array $user User data (optional)
     * @param array $agent Agent data (optional)
     * @return string Parsed content with replaced placeholders
     */
    public static function parse($content, $ticket = [], $user = [], $agent = []) {
        $replacements = self::buildReplacements($ticket, $user, $agent);
        
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Build array of placeholder => value replacements
     * 
     * @param array $ticket Ticket data
     * @param array $user User data
     * @param array $agent Agent data
     * @return array Replacements array
     */
    private static function buildReplacements($ticket, $user, $agent) {
        $replacements = [];
        
        // User information
        if (!empty($user)) {
            $replacements['[Naam]'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            $replacements['[Voornaam]'] = $user['first_name'] ?? '';
            $replacements['[Achternaam]'] = $user['last_name'] ?? '';
            $replacements['[Email]'] = $user['email'] ?? '';
        }
        
        // Ticket information
        if (!empty($ticket)) {
            $replacements['[Ticket nummer]'] = $ticket['ticket_number'] ?? '';
            $replacements['[Ticket Nummer]'] = $ticket['ticket_number'] ?? '';
            $replacements['[Onderwerp]'] = $ticket['subject'] ?? '';
            $replacements['[Categorie]'] = $ticket['category_name'] ?? '';
            $replacements['[Prioriteit]'] = self::getPriorityLabel($ticket['priority'] ?? '');
            $replacements['[Prioriteit niveau]'] = self::getPriorityLabel($ticket['priority'] ?? '');
            $replacements['[Status]'] = self::getStatusLabel($ticket['status'] ?? '');
            $replacements['[Aanmaakdatum]'] = !empty($ticket['created_at']) ? date('d-m-Y H:i', strtotime($ticket['created_at'])) : '';
            $replacements['[Datum]'] = !empty($ticket['created_at']) ? date('d-m-Y', strtotime($ticket['created_at'])) : '';
        }
        
        // Agent information
        if (!empty($agent)) {
            $replacements['[Naam agent]'] = ($agent['first_name'] ?? '') . ' ' . ($agent['last_name'] ?? '');
            $replacements['[Agent]'] = ($agent['first_name'] ?? '') . ' ' . ($agent['last_name'] ?? '');
        } else {
            $replacements['[Naam agent]'] = 'Nog niet toegewezen';
            $replacements['[Agent]'] = 'Nog niet toegewezen';
        }
        
        // Time-based replacements
        $replacements['[Datum en tijd]'] = date('d-m-Y H:i');
        $replacements['[Huidige datum]'] = date('d-m-Y');
        $replacements['[Huidige tijd]'] = date('H:i');
        
        // SLA / Expected resolution time
        if (!empty($ticket['priority'])) {
            $replacements['[Tijdsindicatie]'] = self::getExpectedResolutionTime($ticket['priority']);
            $replacements['[Verwachte oplostijd]'] = self::getExpectedResolutionTime($ticket['priority']);
        }
        
        // Change Management specific
        if (!empty($ticket['id'])) {
            $replacements['[Change nummer]'] = 'CHG-' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT);
            $replacements['[Change ID]'] = 'CHG-' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT);
        }
        
        if (!empty($ticket['title'])) {
            $replacements['[Change titel]'] = $ticket['title'];
        }
        
        if (!empty($ticket['planned_start'])) {
            $replacements['[Geplande uitvoering]'] = date('d-m-Y H:i', strtotime($ticket['planned_start']));
        }
        
        return $replacements;
    }
    
    /**
     * Get priority label in Dutch
     */
    private static function getPriorityLabel($priority) {
        $labels = [
            'low' => 'Laag',
            'medium' => 'Gemiddeld',
            'high' => 'Hoog',
            'urgent' => 'Urgent'
        ];
        return $labels[$priority] ?? 'Gemiddeld';
    }
    
    /**
     * Get status label in Dutch
     */
    private static function getStatusLabel($status) {
        $labels = [
            'open' => 'Open',
            'in_progress' => 'In behandeling',
            'pending' => 'Wachtend',
            'resolved' => 'Opgelost',
            'closed' => 'Gesloten'
        ];
        return $labels[$status] ?? 'Open';
    }
    
    /**
     * Get expected resolution time based on priority
     */
    private static function getExpectedResolutionTime($priority) {
        $times = [
            'urgent' => '4 uur',
            'high' => '1 werkdag',
            'medium' => '3 werkdagen',
            'low' => '5 werkdagen'
        ];
        return $times[$priority] ?? '3 werkdagen';
    }
    
    /**
     * Get available placeholders with descriptions
     * 
     * @return array Array of placeholder => description
     */
    public static function getAvailablePlaceholders() {
        return [
            '[Naam]' => 'Volledige naam van de gebruiker',
            '[Voornaam]' => 'Voornaam van de gebruiker',
            '[Achternaam]' => 'Achternaam van de gebruiker',
            '[Email]' => 'Email adres van de gebruiker',
            '[Ticket nummer]' => 'Ticket nummer',
            '[Onderwerp]' => 'Ticket onderwerp',
            '[Categorie]' => 'Ticket categorie',
            '[Prioriteit]' => 'Ticket prioriteit',
            '[Status]' => 'Ticket status',
            '[Naam agent]' => 'Naam van toegewezen agent',
            '[Tijdsindicatie]' => 'Verwachte oplostijd op basis van prioriteit',
            '[Datum en tijd]' => 'Huidige datum en tijd',
            '[Aanmaakdatum]' => 'Datum waarop ticket is aangemaakt',
            '[Change nummer]' => 'Change request nummer',
            '[Change titel]' => 'Change request titel',
            '[Geplande uitvoering]' => 'Geplande uitvoering datum/tijd'
        ];
    }
}
