
import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { CheckCircle, XCircle, Clock, User, Eye, Save, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';

interface Student {
  id: string;
  name: string;
  email: string;
  course: string;
}

interface ChecklistItem {
  id: string;
  title: string;
  description: string;
  category: string;
}

interface StudentObservation {
  studentId: string;
  itemId: string;
  status: 'not_observed' | 'satisfactory' | 'not_satisfactory';
  notes: string;
  timestamp?: Date;
}

interface MultiStudentObservationProps {
  students: Student[];
  checklist: ChecklistItem[];
  onComplete: () => void;
  onCancel: () => void;
}

const MultiStudentObservation: React.FC<MultiStudentObservationProps> = ({
  students,
  checklist,
  onComplete,
  onCancel
}) => {
  const [observations, setObservations] = useState<StudentObservation[]>([]);
  const [activeStudent, setActiveStudent] = useState(students[0]?.id || '');
  const [notes, setNotes] = useState<{ [key: string]: string }>({});

  const getObservation = (studentId: string, itemId: string) => {
    return observations.find(obs => obs.studentId === studentId && obs.itemId === itemId);
  };

  const updateObservation = (studentId: string, itemId: string, status: 'satisfactory' | 'not_satisfactory') => {
    const observationKey = `${studentId}-${itemId}`;
    const note = notes[observationKey] || '';
    
    setObservations(prev => {
      const filtered = prev.filter(obs => !(obs.studentId === studentId && obs.itemId === itemId));
      return [...filtered, {
        studentId,
        itemId,
        status,
        notes: note,
        timestamp: new Date()
      }];
    });
    
    toast.success(`Observation recorded for ${students.find(s => s.id === studentId)?.name}`);
  };

  const handleNotesChange = (studentId: string, itemId: string, value: string) => {
    const key = `${studentId}-${itemId}`;
    setNotes(prev => ({ ...prev, [key]: value }));
  };

  const getStudentProgress = (studentId: string) => {
    const studentObservations = observations.filter(obs => obs.studentId === studentId);
    return {
      completed: studentObservations.length,
      total: checklist.length,
      satisfactory: studentObservations.filter(obs => obs.status === 'satisfactory').length,
      notSatisfactory: studentObservations.filter(obs => obs.status === 'not_satisfactory').length
    };
  };

  const saveAndComplete = () => {
    // In a real implementation, this would save to the backend
    toast.success(`Observations saved for ${students.length} students`);
    onComplete();
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'satisfactory':
        return <CheckCircle className="w-4 h-4 text-green-600" />;
      case 'not_satisfactory':
        return <XCircle className="w-4 h-4 text-red-600" />;
      default:
        return <Clock className="w-4 h-4 text-gray-400" />;
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold">Multi-Student Observation</h2>
          <p className="text-gray-600">Observing {students.length} students</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={onCancel}>
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back
          </Button>
          <Button onClick={saveAndComplete}>
            <Save className="w-4 h-4 mr-2" />
            Save & Complete
          </Button>
        </div>
      </div>

      {/* Student Progress Overview */}
      <Card>
        <CardHeader>
          <CardTitle>Student Progress Overview</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {students.map(student => {
              const progress = getStudentProgress(student.id);
              const completionRate = (progress.completed / progress.total) * 100;
              
              return (
                <div 
                  key={student.id}
                  className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                    activeStudent === student.id ? 'border-blue-500 bg-blue-50' : 'hover:bg-gray-50'
                  }`}
                  onClick={() => setActiveStudent(student.id)}
                >
                  <div className="flex items-center gap-2 mb-2">
                    <User className="w-4 h-4" />
                    <h4 className="font-medium">{student.name}</h4>
                  </div>
                  <p className="text-sm text-gray-600 mb-3">{student.course}</p>
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Progress</span>
                      <span>{progress.completed}/{progress.total}</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        className="bg-blue-600 h-2 rounded-full transition-all"
                        style={{ width: `${completionRate}%` }}
                      ></div>
                    </div>
                    <div className="flex gap-4 text-xs">
                      <span className="text-green-600">✓ {progress.satisfactory}</span>
                      <span className="text-red-600">✗ {progress.notSatisfactory}</span>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Observation Interface */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Eye className="w-5 h-5" />
            Observing: {students.find(s => s.id === activeStudent)?.name}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-6">
            {checklist.map(item => {
              const observation = getObservation(activeStudent, item.id);
              const notesKey = `${activeStudent}-${item.id}`;
              
              return (
                <div key={item.id} className="border rounded-lg p-4">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        {getStatusIcon(observation?.status || 'not_observed')}
                        <h4 className="font-medium">{item.title}</h4>
                        <Badge variant="outline" className="text-xs">
                          {item.category}
                        </Badge>
                      </div>
                      <p className="text-sm text-gray-600">{item.description}</p>
                    </div>
                  </div>

                  <div className="space-y-3">
                    <div>
                      <Label htmlFor={`notes-${notesKey}`} className="text-sm">
                        Observation Notes
                      </Label>
                      <Textarea
                        id={`notes-${notesKey}`}
                        placeholder="Record your observations here..."
                        value={notes[notesKey] || ''}
                        onChange={(e) => handleNotesChange(activeStudent, item.id, e.target.value)}
                        rows={3}
                        className="mt-1"
                      />
                    </div>

                    {!observation || observation.status === 'not_observed' ? (
                      <div className="flex gap-3">
                        <Button
                          onClick={() => updateObservation(activeStudent, item.id, 'satisfactory')}
                          className="flex-1 bg-green-600 hover:bg-green-700"
                        >
                          <CheckCircle className="w-4 h-4 mr-2" />
                          Satisfactory
                        </Button>
                        <Button
                          onClick={() => updateObservation(activeStudent, item.id, 'not_satisfactory')}
                          variant="destructive"
                          className="flex-1"
                        >
                          <XCircle className="w-4 h-4 mr-2" />
                          Not Satisfactory
                        </Button>
                      </div>
                    ) : (
                      <div className={`p-3 rounded-lg ${
                        observation.status === 'satisfactory' 
                          ? 'bg-green-50 border border-green-200' 
                          : 'bg-red-50 border border-red-200'
                      }`}>
                        <div className="flex items-center justify-between mb-2">
                          <div className="flex items-center gap-2">
                            {getStatusIcon(observation.status)}
                            <span className="font-medium">
                              {observation.status.replace('_', ' ').toUpperCase()}
                            </span>
                          </div>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                              setObservations(prev => prev.filter(obs => 
                                !(obs.studentId === activeStudent && obs.itemId === item.id)
                              ));
                            }}
                          >
                            Re-observe
                          </Button>
                        </div>
                        {observation.notes && (
                          <p className="text-sm text-gray-700">{observation.notes}</p>
                        )}
                        <p className="text-xs text-gray-500 mt-1">
                          Observed at {observation.timestamp?.toLocaleTimeString()}
                        </p>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default MultiStudentObservation;
