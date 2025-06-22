
import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { CheckCircle, XCircle, Clock, User, Eye, Save, ArrowLeft, Users } from 'lucide-react';
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
    
    const student = students.find(s => s.id === studentId);
    toast.success(`Observation recorded for ${student?.name}`);
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
          <h2 className="text-2xl font-bold flex items-center gap-2">
            <Users className="w-6 h-6" />
            Multi-Student Observation Session
          </h2>
          <p className="text-gray-600">Observing {students.length} students simultaneously</p>
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
          <CardTitle>Session Overview</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {students.map(student => {
              const progress = getStudentProgress(student.id);
              const completionRate = (progress.completed / progress.total) * 100;
              
              return (
                <div key={student.id} className="p-4 border rounded-lg">
                  <div className="flex items-center gap-2 mb-2">
                    <User className="w-4 h-4" />
                    <h4 className="font-medium text-sm">{student.name}</h4>
                  </div>
                  <div className="space-y-2">
                    <div className="flex justify-between text-xs">
                      <span>Progress</span>
                      <span>{progress.completed}/{progress.total}</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-1.5">
                      <div 
                        className="bg-blue-600 h-1.5 rounded-full transition-all"
                        style={{ width: `${completionRate}%` }}
                      ></div>
                    </div>
                    <div className="flex gap-2 text-xs">
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

      {/* Multi-Student Observation Grid */}
      <div className="space-y-6">
        {checklist.map(item => (
          <Card key={item.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <CardTitle className="text-lg">{item.title}</CardTitle>
                  <div className="flex items-center gap-2 mt-1">
                    <Badge variant="outline" className="text-xs">
                      {item.category}
                    </Badge>
                  </div>
                  <p className="text-sm text-gray-600 mt-2">{item.description}</p>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                {students.map(student => {
                  const observation = getObservation(student.id, item.id);
                  const notesKey = `${student.id}-${item.id}`;
                  
                  return (
                    <div key={student.id} className="border rounded-lg p-4 space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <User className="w-4 h-4" />
                          <span className="font-medium text-sm">{student.name}</span>
                        </div>
                        {observation && (
                          <div className="flex items-center gap-1">
                            {getStatusIcon(observation.status)}
                            <span className="text-xs text-gray-500">
                              {observation.timestamp?.toLocaleTimeString()}
                            </span>
                          </div>
                        )}
                      </div>

                      <div>
                        <Label className="text-xs">Observation Notes</Label>
                        <Textarea
                          placeholder="Record observations..."
                          value={notes[notesKey] || ''}
                          onChange={(e) => handleNotesChange(student.id, item.id, e.target.value)}
                          rows={2}
                          className="mt-1 text-sm"
                        />
                      </div>

                      {!observation || observation.status === 'not_observed' ? (
                        <div className="flex gap-2">
                          <Button
                            onClick={() => updateObservation(student.id, item.id, 'satisfactory')}
                            size="sm"
                            className="flex-1 bg-green-600 hover:bg-green-700 text-xs"
                          >
                            <CheckCircle className="w-3 h-3 mr-1" />
                            Satisfactory
                          </Button>
                          <Button
                            onClick={() => updateObservation(student.id, item.id, 'not_satisfactory')}
                            variant="destructive"
                            size="sm"
                            className="flex-1 text-xs"
                          >
                            <XCircle className="w-3 h-3 mr-1" />
                            Not Satisfactory
                          </Button>
                        </div>
                      ) : (
                        <div className={`p-2 rounded-lg text-sm ${
                          observation.status === 'satisfactory' 
                            ? 'bg-green-50 border border-green-200' 
                            : 'bg-red-50 border border-red-200'
                        }`}>
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1">
                              {getStatusIcon(observation.status)}
                              <span className="font-medium text-xs">
                                {observation.status.replace('_', ' ').toUpperCase()}
                              </span>
                            </div>
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => {
                                setObservations(prev => prev.filter(obs => 
                                  !(obs.studentId === student.id && obs.itemId === item.id)
                                ));
                              }}
                              className="h-6 px-2 text-xs"
                            >
                              Reset
                            </Button>
                          </div>
                          {observation.notes && (
                            <p className="text-xs text-gray-700 mt-1">{observation.notes}</p>
                          )}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
};

export default MultiStudentObservation;
