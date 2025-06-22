
import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Printer, Download, CheckCircle, XCircle, Clock } from 'lucide-react';

interface ChecklistItem {
  id: string;
  title: string;
  description: string;
  category: string;
  status: 'not_started' | 'in_progress' | 'satisfactory' | 'not_satisfactory';
  evidence?: File[];
  assessorNotes?: string;
  dateCompleted?: Date;
  assessedBy?: string;
}

interface Student {
  id: string;
  name: string;
  email: string;
  course: string;
}

interface PrintReportProps {
  checklist: ChecklistItem[];
  student?: Student;
  isTrainerView?: boolean;
  onClose?: () => void;
}

const PrintReport: React.FC<PrintReportProps> = ({ 
  checklist, 
  student, 
  isTrainerView = false,
  onClose 
}) => {
  const handlePrint = () => {
    window.print();
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'satisfactory':
        return <CheckCircle className="w-4 h-4 text-green-600" />;
      case 'not_satisfactory':
        return <XCircle className="w-4 h-4 text-red-600" />;
      case 'in_progress':
        return <Clock className="w-4 h-4 text-yellow-600" />;
      default:
        return <Clock className="w-4 h-4 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'satisfactory':
        return 'bg-green-100 text-green-800';
      case 'not_satisfactory':
        return 'bg-red-100 text-red-800';
      case 'in_progress':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const completedItems = checklist.filter(item => 
    item.status === 'satisfactory' || item.status === 'not_satisfactory'
  );

  const satisfactoryCount = checklist.filter(item => item.status === 'satisfactory').length;
  const totalItems = checklist.length;
  const completionRate = totalItems > 0 ? (satisfactoryCount / totalItems) * 100 : 0;

  return (
    <div className="max-w-4xl mx-auto p-6 bg-white">
      {/* Print Controls - Hidden during print */}
      <div className="print:hidden mb-6 flex justify-between items-center">
        <h1 className="text-2xl font-bold">Observation Report</h1>
        <div className="flex gap-2">
          <Button onClick={handlePrint} className="flex items-center gap-2">
            <Printer className="w-4 h-4" />
            Print Report
          </Button>
          {onClose && (
            <Button variant="outline" onClick={onClose}>
              Close
            </Button>
          )}
        </div>
      </div>

      {/* Report Header */}
      <div className="mb-8">
        <div className="text-center mb-6">
          <h1 className="text-3xl font-bold mb-2">Workplace Observation Report</h1>
          <p className="text-gray-600">Assessment Summary and Progress Report</p>
        </div>

        {/* Student Information */}
        {student && (
          <Card className="mb-6">
            <CardHeader>
              <CardTitle>Student Information</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 gap-4">
              <div>
                <strong>Name:</strong> {student.name}
              </div>
              <div>
                <strong>Email:</strong> {student.email}
              </div>
              <div>
                <strong>Course:</strong> {student.course}
              </div>
              <div>
                <strong>Report Generated:</strong> {new Date().toLocaleDateString()}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Summary Statistics */}
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>Assessment Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-4 gap-4 text-center">
              <div>
                <div className="text-2xl font-bold text-green-600">{satisfactoryCount}</div>
                <div className="text-sm text-gray-600">Satisfactory</div>
              </div>
              <div>
                <div className="text-2xl font-bold text-red-600">
                  {checklist.filter(item => item.status === 'not_satisfactory').length}
                </div>
                <div className="text-sm text-gray-600">Not Satisfactory</div>
              </div>
              <div>
                <div className="text-2xl font-bold text-blue-600">{totalItems}</div>
                <div className="text-sm text-gray-600">Total Items</div>
              </div>
              <div>
                <div className="text-2xl font-bold text-purple-600">{Math.round(completionRate)}%</div>
                <div className="text-sm text-gray-600">Success Rate</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Detailed Assessment Items */}
      <div className="space-y-4">
        <h2 className="text-xl font-bold mb-4">Detailed Assessment Results</h2>
        
        {checklist.map((item, index) => (
          <Card key={item.id} className="break-inside-avoid">
            <CardHeader className="pb-3">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <span className="text-sm font-medium text-gray-500">
                      Item {index + 1}
                    </span>
                    {getStatusIcon(item.status)}
                    <CardTitle className="text-lg">{item.title}</CardTitle>
                  </div>
                  <p className="text-gray-600 text-sm mb-2">{item.description}</p>
                  <Badge variant="outline" className="text-xs">
                    {item.category}
                  </Badge>
                </div>
                <Badge className={`ml-4 ${getStatusColor(item.status)}`}>
                  {item.status.replace('_', ' ').toUpperCase()}
                </Badge>
              </div>
            </CardHeader>
            
            {(item.status === 'satisfactory' || item.status === 'not_satisfactory') && (
              <CardContent className="pt-0">
                {item.assessorNotes && (
                  <div className="mb-3">
                    <strong className="text-sm">Assessor Notes:</strong>
                    <p className="text-sm text-gray-700 mt-1 p-2 bg-gray-50 rounded">
                      {item.assessorNotes}
                    </p>
                  </div>
                )}
                
                <div className="flex justify-between items-center text-xs text-gray-600">
                  <span>
                    Assessed by: {item.assessedBy || 'Unknown'}
                  </span>
                  <span>
                    Date: {item.dateCompleted?.toLocaleDateString()} at{' '}
                    {item.dateCompleted?.toLocaleTimeString()}
                  </span>
                </div>
              </CardContent>
            )}
          </Card>
        ))}
      </div>

      {/* Report Footer */}
      <div className="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-600">
        <p>This report was generated on {new Date().toLocaleString()}</p>
        <p className="mt-2">
          {isTrainerView ? 'Trainer Assessment Report' : 'Student Progress Report'} - Workplace Observation Checklist
        </p>
      </div>

      {/* Print Styles */}
      <style>
        {`
          @media print {
            body {
              margin: 0;
              padding: 0;
            }
            .print\\:hidden {
              display: none !important;
            }
            .break-inside-avoid {
              break-inside: avoid;
            }
          }
        `}
      </style>
    </div>
  );
};

export default PrintReport;
