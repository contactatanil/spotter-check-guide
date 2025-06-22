import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { CheckCircle, XCircle, Clock, FileText, Download, Eye, Users, Printer } from 'lucide-react';
import { toast } from 'sonner';
import StudentSelector from './StudentSelector';
import MultiStudentObservation from './MultiStudentObservation';
import PrintReport from './PrintReport';

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
  status: 'active' | 'inactive';
  lastActivity?: Date;
}

interface AssessorInterfaceProps {
  checklist: ChecklistItem[];
  onUpdateItem: (itemId: string, updates: Partial<ChecklistItem>) => void;
}

const AssessorInterface: React.FC<AssessorInterfaceProps> = ({ checklist, onUpdateItem }) => {
  const [assessorNotes, setAssessorNotes] = useState<{ [key: string]: string }>({});
  const [selectedStudents, setSelectedStudents] = useState<string[]>([]);
  const [observationMode, setObservationMode] = useState<'assessment' | 'student_selection' | 'observation'>('assessment');
  const [showPrintDialog, setShowPrintDialog] = useState(false);

  // Mock student data - in real implementation, this would come from Moodle
  const mockStudents: Student[] = [
    {
      id: '1',
      name: 'Alice Johnson',
      email: 'alice.johnson@example.com',
      course: 'Workplace Skills Training',
      status: 'active',
      lastActivity: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000)
    },
    {
      id: '2',
      name: 'Bob Smith',
      email: 'bob.smith@example.com',
      course: 'Workplace Skills Training',
      status: 'active',
      lastActivity: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000)
    },
    {
      id: '3',
      name: 'Carol Davis',
      email: 'carol.davis@example.com',
      course: 'Workplace Skills Training',
      status: 'active',
      lastActivity: new Date()
    },
    {
      id: '4',
      name: 'David Wilson',
      email: 'david.wilson@example.com',
      course: 'Advanced Skills Training',
      status: 'inactive',
      lastActivity: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
    }
  ];

  const mockStudent = {
    id: '1',
    name: 'Current Student',
    email: 'student@example.com',
    course: 'Workplace Skills Training'
  };

  const handleAssessment = (itemId: string, assessment: 'satisfactory' | 'not_satisfactory') => {
    const notes = assessorNotes[itemId] || '';
    
    onUpdateItem(itemId, {
      status: assessment,
      assessorNotes: notes,
      dateCompleted: new Date(),
      assessedBy: 'Current Assessor'
    });

    toast.success(`Item marked as ${assessment.replace('_', ' ')}`);
    setAssessorNotes(prev => ({ ...prev, [itemId]: '' }));
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'satisfactory':
        return <CheckCircle className="w-5 h-5 text-green-600" />;
      case 'not_satisfactory':
        return <XCircle className="w-5 h-5 text-red-600" />;
      case 'in_progress':
        return <Clock className="w-5 h-5 text-yellow-600" />;
      default:
        return <FileText className="w-5 h-5 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'satisfactory':
        return 'default';
      case 'not_satisfactory':
        return 'destructive';
      case 'in_progress':
        return 'secondary';
      default:
        return 'outline';
    }
  };

  const itemsToAssess = checklist.filter(item => 
    item.status === 'in_progress' || item.status === 'satisfactory' || item.status === 'not_satisfactory'
  );

  const pendingItems = checklist.filter(item => item.status === 'in_progress');

  // Convert checklist items to the format expected by MultiStudentObservation
  const observationChecklist = checklist.map(item => ({
    id: item.id,
    title: item.title,
    description: item.description,
    category: item.category
  }));

  if (observationMode === 'student_selection') {
    return (
      <div className="space-y-6">
        <StudentSelector
          students={mockStudents}
          selectedStudents={selectedStudents}
          onStudentSelect={setSelectedStudents}
          onStartObservation={(studentIds) => {
            setSelectedStudents(studentIds);
            setObservationMode('observation');
          }}
        />
        <Button variant="outline" onClick={() => setObservationMode('assessment')}>
          Back to Assessment Dashboard
        </Button>
      </div>
    );
  }

  if (observationMode === 'observation') {
    const selectedStudentData = mockStudents.filter(s => selectedStudents.includes(s.id));
    return (
      <MultiStudentObservation
        students={selectedStudentData}
        checklist={observationChecklist}
        onComplete={() => {
          setObservationMode('assessment');
          setSelectedStudents([]);
        }}
        onCancel={() => {
          setObservationMode('student_selection');
        }}
      />
    );
  }

  return (
    <div className="space-y-6">
      <div className="mb-6">
        <div className="flex justify-between items-center">
          <div>
            <h2 className="text-2xl font-bold mb-2">Assessment Dashboard</h2>
            <p className="text-gray-600">Review student submissions and conduct live observations.</p>
          </div>
          <div className="flex gap-2">
            <Dialog open={showPrintDialog} onOpenChange={setShowPrintDialog}>
              <DialogTrigger asChild>
                <Button variant="outline" className="flex items-center gap-2">
                  <Printer className="w-4 h-4" />
                  Print Report
                </Button>
              </DialogTrigger>
              <DialogContent className="max-w-6xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                  <DialogTitle>Trainer Assessment Report</DialogTitle>
                </DialogHeader>
                <PrintReport 
                  checklist={checklist} 
                  student={mockStudent}
                  isTrainerView={true}
                  onClose={() => setShowPrintDialog(false)}
                />
              </DialogContent>
            </Dialog>
            
            <Button 
              onClick={() => setObservationMode('student_selection')}
              className="flex items-center gap-2"
            >
              <Users className="w-4 h-4" />
              Start Live Observation
            </Button>
          </div>
        </div>
        
        {pendingItems.length > 0 && (
          <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p className="text-yellow-800">
              <strong>{pendingItems.length}</strong> item(s) are awaiting your assessment.
            </p>
          </div>
        )}
      </div>

      <Tabs defaultValue="submissions">
        <TabsList>
          <TabsTrigger value="submissions">Student Submissions</TabsTrigger>
          <TabsTrigger value="filter">Filter & Sort</TabsTrigger>
        </TabsList>

        <TabsContent value="submissions" className="space-y-4">
          {/* Quick Assessment Filter */}
          <Card>
            <CardHeader>
              <CardTitle>Filter Items</CardTitle>
            </CardHeader>
            <CardContent>
              <Select defaultValue="all">
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Items</SelectItem>
                  <SelectItem value="pending">Pending Assessment</SelectItem>
                  <SelectItem value="satisfactory">Satisfactory</SelectItem>
                  <SelectItem value="not_satisfactory">Not Satisfactory</SelectItem>
                </SelectContent>
              </Select>
            </CardContent>
          </Card>

          {/* Assessment Items */}
          {itemsToAssess.map(item => (
            <Card key={item.id} className="border-l-4 border-l-purple-500">
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      {getStatusIcon(item.status)}
                      <CardTitle className="text-lg">{item.title}</CardTitle>
                      <Badge variant={getStatusColor(item.status) as any}>
                        {item.status.replace('_', ' ').toUpperCase()}
                      </Badge>
                    </div>
                    <p className="text-gray-600 mb-2">{item.description}</p>
                    <Badge variant="outline" className="text-xs">
                      {item.category}
                    </Badge>
                  </div>
                </div>
              </CardHeader>

              <CardContent className="space-y-4">
                {/* Evidence Review Section */}
                {item.evidence && item.evidence.length > 0 && (
                  <div className="space-y-3">
                    <Label className="text-sm font-medium">Submitted Evidence</Label>
                    <div className="space-y-2">
                      {item.evidence.map((file, index) => (
                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                          <div className="flex items-center gap-2">
                            <FileText className="w-4 h-4" />
                            <span className="text-sm font-medium">{file.name}</span>
                            <span className="text-xs text-gray-500">
                              ({(file.size / 1024 / 1024).toFixed(2)} MB)
                            </span>
                          </div>
                          <div className="flex gap-2">
                            <Button variant="outline" size="sm">
                              <Eye className="w-4 h-4 mr-1" />
                              Preview
                            </Button>
                            <Button variant="outline" size="sm">
                              <Download className="w-4 h-4 mr-1" />
                              Download
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Assessment Section */}
                <div className="space-y-3">
                  <Label className="text-sm font-medium">Assessment</Label>
                  
                  {item.status === 'in_progress' && (
                    <div className="space-y-4">
                      <div>
                        <Label htmlFor={`assessment-notes-${item.id}`} className="text-sm">
                          Assessment Notes
                        </Label>
                        <Textarea
                          id={`assessment-notes-${item.id}`}
                          placeholder="Provide feedback on the student's evidence and performance..."
                          value={assessorNotes[item.id] || ''}
                          onChange={(e) => setAssessorNotes(prev => ({ 
                            ...prev, 
                            [item.id]: e.target.value 
                          }))}
                          rows={4}
                          className="mt-1"
                        />
                      </div>

                      <div className="flex gap-3">
                        <Button
                          onClick={() => handleAssessment(item.id, 'satisfactory')}
                          className="flex-1 bg-green-600 hover:bg-green-700"
                        >
                          <CheckCircle className="w-4 h-4 mr-2" />
                          Mark as Satisfactory
                        </Button>
                        <Button
                          onClick={() => handleAssessment(item.id, 'not_satisfactory')}
                          variant="destructive"
                          className="flex-1"
                        >
                          <XCircle className="w-4 h-4 mr-2" />
                          Mark as Not Satisfactory
                        </Button>
                      </div>
                    </div>
                  )}

                  {(item.status === 'satisfactory' || item.status === 'not_satisfactory') && (
                    <div className={`p-4 rounded-lg ${
                      item.status === 'satisfactory' 
                        ? 'bg-green-50 border border-green-200' 
                        : 'bg-red-50 border border-red-200'
                    }`}>
                      <div className="flex items-center justify-between mb-2">
                        <div className="flex items-center gap-2">
                          {getStatusIcon(item.status)}
                          <span className="font-medium">
                            Assessment: {item.status.replace('_', ' ').toUpperCase()}
                          </span>
                        </div>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => onUpdateItem(item.id, { status: 'in_progress' })}
                        >
                          Reassess
                        </Button>
                      </div>
                      
                      {item.assessorNotes && (
                        <div className="mb-2">
                          <strong className="text-sm">Notes:</strong>
                          <p className="text-sm text-gray-700 mt-1">{item.assessorNotes}</p>
                        </div>
                      )}
                      
                      <p className="text-xs text-gray-600">
                        Assessed by {item.assessedBy} on{' '}
                        {item.dateCompleted?.toLocaleDateString()} at{' '}
                        {item.dateCompleted?.toLocaleTimeString()}
                      </p>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}

          {itemsToAssess.length === 0 && (
            <Card>
              <CardContent className="text-center py-8">
                <FileText className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No Submissions Yet</h3>
                <p className="text-gray-600">
                  Students haven't submitted any evidence for assessment yet.
                </p>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="filter">
          <Card className="bg-purple-50 border-purple-200">
            <CardContent className="pt-4">
              <h3 className="font-medium mb-2">Assessment Guidelines</h3>
              <ul className="text-sm text-gray-700 space-y-1">
                <li>• Review all submitted evidence carefully before making an assessment</li>
                <li>• Provide detailed feedback in your assessment notes</li>
                <li>• Use "Not Satisfactory" when evidence doesn't meet requirements</li>
                <li>• Students can resubmit evidence after receiving feedback</li>
                <li>• You can reassess items at any time by clicking "Reassess"</li>
                <li>• Use "Start Live Observation" to observe multiple students simultaneously</li>
                <li>• Use "Print Report" to generate comprehensive assessment reports</li>
              </ul>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default AssessorInterface;
