
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/excellib.class.php');

/**
 * Export manager for observation checklist reports
 */
class export_manager {

    /**
     * Export data to CSV format
     */
    public static function export_csv($cm, $reporttype, $data, $filename) {
        global $CFG;
        
        $csvexport = new \csv_export_writer();
        $csvexport->set_filename($filename);
        
        switch ($reporttype) {
            case 'grading':
                $csvexport->add_data([
                    get_string('firstname'),
                    get_string('lastname'),
                    get_string('email'),
                    get_string('itemsassessed', 'observationchecklist'),
                    get_string('satisfactory', 'observationchecklist'),
                    get_string('not_satisfactory', 'observationchecklist'),
                    get_string('lastactivity')
                ]);
                
                foreach ($data as $row) {
                    $csvexport->add_data([
                        $row->firstname,
                        $row->lastname,
                        $row->email,
                        $row->items_assessed,
                        $row->satisfactory,
                        $row->not_satisfactory,
                        userdate($row->last_activity)
                    ]);
                }
                break;
                
            case 'attempts':
                $csvexport->add_data([
                    get_string('student'),
                    get_string('itemtext', 'observationchecklist'),
                    get_string('category', 'observationchecklist'),
                    get_string('status'),
                    get_string('notes', 'observationchecklist'),
                    get_string('dateassessed', 'observationchecklist'),
                    get_string('assessedby', 'observationchecklist')
                ]);
                
                foreach ($data as $row) {
                    $csvexport->add_data([
                        fullname($row),
                        $row->itemtext,
                        $row->category,
                        $row->status,
                        $row->assessornotes,
                        userdate($row->dateassessed),
                        $row->assessor_firstname . ' ' . $row->assessor_lastname
                    ]);
                }
                break;
        }
        
        $csvexport->download_file();
    }

    /**
     * Export data to Excel format
     */
    public static function export_excel($cm, $reporttype, $data, $filename) {
        global $CFG;
        
        $workbook = new \MoodleExcelWorkbook($filename);
        $worksheet = $workbook->add_worksheet(get_string('report', 'observationchecklist'));
        
        $row = 0;
        
        switch ($reporttype) {
            case 'grading':
                // Headers
                $headers = [
                    get_string('firstname'),
                    get_string('lastname'),
                    get_string('email'),
                    get_string('itemsassessed', 'observationchecklist'),
                    get_string('satisfactory', 'observationchecklist'),
                    get_string('not_satisfactory', 'observationchecklist'),
                    get_string('lastactivity')
                ];
                
                foreach ($headers as $col => $header) {
                    $worksheet->write_string($row, $col, $header);
                }
                $row++;
                
                foreach ($data as $record) {
                    $worksheet->write_string($row, 0, $record->firstname);
                    $worksheet->write_string($row, 1, $record->lastname);
                    $worksheet->write_string($row, 2, $record->email);
                    $worksheet->write_number($row, 3, $record->items_assessed);
                    $worksheet->write_number($row, 4, $record->satisfactory);
                    $worksheet->write_number($row, 5, $record->not_satisfactory);
                    $worksheet->write_string($row, 6, userdate($record->last_activity));
                    $row++;
                }
                break;
        }
        
        $workbook->close();
    }

    /**
     * Export data to PDF format
     */
    public static function export_pdf($cm, $reporttype, $data, $filename) {
        global $CFG;
        
        require_once($CFG->libdir . '/pdflib.php');
        
        $pdf = new \pdf();
        $pdf->SetCreator('Moodle Observation Checklist');
        $pdf->SetTitle(get_string('report', 'observationchecklist'));
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, get_string('report', 'observationchecklist'), 0, 1, 'C');
        
        $pdf->Ln(10);
        
        switch ($reporttype) {
            case 'overview':
                $pdf->SetFont('helvetica', '', 12);
                foreach ($data as $key => $value) {
                    $pdf->Cell(0, 8, ucfirst(str_replace('_', ' ', $key)) . ': ' . $value, 0, 1);
                }
                break;
        }
        
        $pdf->Output($filename . '.pdf', 'D');
    }
}
