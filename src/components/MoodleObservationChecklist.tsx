
import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Upload, CheckCircle, XCircle, Clock, FileText, User, Award } from 'lucide-react';
import StudentInterface from './StudentInterface';
import AssessorInterface from './AssessorInterface';
import ProgressTracker from './ProgressTracker';

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

interface UserData {
  id: string;
  name: string;
  role: 'student' | 'assessor' | 'instructor';
  email: string;
}

const MoodleObservationChecklist: React.FC = () => {
  const [user, setUser] = useState<UserData>({
    id: '1',
    name: 'John Doe',
    role: 'student',
    email: 'john.doe@example.com'
  });

  const [checklist, setChecklist] = useState<ChecklistItem[]>([
    {
      id: '1',
      title: 'Safety Protocol Assessment',
      description: 'Demonstrate proper safety procedures in workplace environment',
      category: 'Safety',
      status: 'not_started'
    },
    {
      id: '2',
      title: 'Technical Skills Evaluation',
      description: 'Show competency in required technical skills',
      category: 'Technical',
      status: 'in_progress'
    },
    {
      id: '3',
      title: 'Communication Assessment',
      description: 'Effective communication with team members and supervisors',
      category: 'Communication',
      status: 'satisfactory',
      dateCompleted: new Date(),
      assessedBy: 'Jane Smith'
    }
  ]);

  const [activeTab, setActiveTab] = useState('overview');

  const getProgressPercentage = () => {
    const completed = checklist.filter(item => 
      item.status === 'satisfactory' || item.status === 'not_satisfactory'
    ).length;
    return (completed / checklist.length) * 100;
  };

  const getStatusCounts = () => {
    return {
      satisfactory: checklist.filter(item => item.status === 'satisfactory').length,
      not_satisfactory: checklist.filter(item => item.status === 'not_satisfactory').length,
      in_progress: checklist.filter(item => item.status === 'in_progress').length,
      not_started: checklist.filter(item => item.status === 'not_started').length
    };
  };

  const handleRoleSwitch = (newRole: 'student' | 'assessor') => {
    setUser(prev => ({ ...prev, role: newRole }));
  };

  const updateChecklistItem = (itemId: string, updates: Partial<ChecklistItem>) => {
    setChecklist(prev => prev.map(item => 
      item.id === itemId ? { ...item, ...updates } : item
    ));
  };

  const statusCounts = getStatusCounts();

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <div className="flex justify-between items-center mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Observation Checklist</h1>
              <p className="text-gray-600">Workplace Assessment Tool</p>
            </div>
            
            {/* Role Switcher for Demo */}
            <div className="flex gap-2">
              <Button
                variant={user.role === 'student' ? 'default' : 'outline'}
                onClick={() => handleRoleSwitch('student')}
                size="sm"
              >
                <User className="w-4 h-4 mr-2" />
                Student View
              </Button>
              <Button
                variant={user.role === 'assessor' ? 'default' : 'outline'}
                onClick={() => handleRoleSwitch('assessor')}
                size="sm"
              >
                <Award className="w-4 h-4 mr-2" />
                Assessor View
              </Button>
            </div>
          </div>

          {/* User Info */}
          <Card className="mb-6">
            <CardContent className="pt-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <User className="w-6 h-6 text-blue-600" />
                  </div>
                  <div>
                    <h3 className="font-semibold">{user.name}</h3>
                    <p className="text-sm text-gray-600">{user.role.toUpperCase()} • {user.email}</p>
                  </div>
                </div>
                <Badge variant={user.role === 'student' ? 'default' : 'secondary'}>
                  {user.role.toUpperCase()}
                </Badge>
              </div>
            </CardContent>
          </Card>

          {/* Progress Overview */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <Card>
              <CardContent className="pt-4">
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-green-600" />
                  <div>
                    <p className="text-sm text-gray-600">Satisfactory</p>
                    <p className="text-2xl font-bold text-green-600">{statusCounts.satisfactory}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-4">
                <div className="flex items-center gap-2">
                  <XCircle className="w-5 h-5 text-red-600" />
                  <div>
                    <p className="text-sm text-gray-600">Not Satisfactory</p>
                    <p className="text-2xl font-bold text-red-600">{statusCounts.not_satisfactory}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-4">
                <div className="flex items-center gap-2">
                  <Clock className="w-5 h-5 text-yellow-600" />
                  <div>
                    <p className="text-sm text-gray-600">In Progress</p>
                    <p className="text-2xl font-bold text-yellow-600">{statusCounts.in_progress}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-4">
                <div className="flex items-center gap-2">
                  <FileText className="w-5 h-5 text-gray-600" />
                  <div>
                    <p className="text-sm text-gray-600">Not Started</p>
                    <p className="text-2xl font-bold text-gray-600">{statusCounts.not_started}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Overall Progress */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle>Overall Progress</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">Completion Rate</span>
                  <span className="text-sm font-medium">{Math.round(getProgressPercentage())}%</span>
                </div>
                <Progress value={getProgressPercentage()} className="h-3" />
                <p className="text-xs text-gray-500">
                  {checklist.filter(item => item.status === 'satisfactory' || item.status === 'not_satisfactory').length} of {checklist.length} items assessed
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Main Content */}
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="interface">
              {user.role === 'student' ? 'My Progress' : 'Assessment'}
            </TabsTrigger>
            <TabsTrigger value="progress">Progress Tracking</TabsTrigger>
          </TabsList>

          <TabsContent value="overview" className="space-y-4">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle>Assessment Items</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    {checklist.map(item => (
                      <div key={item.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div className="flex-1">
                          <h4 className="font-medium">{item.title}</h4>
                          <p className="text-sm text-gray-600">{item.category}</p>
                        </div>
                        <Badge 
                          variant={
                            item.status === 'satisfactory' ? 'default' :
                            item.status === 'not_satisfactory' ? 'destructive' :
                            item.status === 'in_progress' ? 'secondary' : 'outline'
                          }
                        >
                          {item.status.replace('_', ' ').toUpperCase()}
                        </Badge>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Recent Activity</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    <div className="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                      <CheckCircle className="w-5 h-5 text-green-600" />
                      <div className="flex-1">
                        <p className="text-sm font-medium">Communication Assessment completed</p>
                        <p className="text-xs text-gray-600">Assessed by Jane Smith • Today</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                      <Upload className="w-5 h-5 text-blue-600" />
                      <div className="flex-1">
                        <p className="text-sm font-medium">Evidence uploaded for Technical Skills</p>
                        <p className="text-xs text-gray-600">2 hours ago</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                      <Clock className="w-5 h-5 text-yellow-600" />
                      <div className="flex-1">
                        <p className="text-sm font-medium">Technical Skills Assessment started</p>
                        <p className="text-xs text-gray-600">Yesterday</p>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="interface">
            {user.role === 'student' ? (
              <StudentInterface 
                checklist={checklist} 
                onUpdateItem={updateChecklistItem}
              />
            ) : (
              <AssessorInterface 
                checklist={checklist} 
                onUpdateItem={updateChecklistItem}
              />
            )}
          </TabsContent>

          <TabsContent value="progress">
            <ProgressTracker checklist={checklist} />
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
};

export default MoodleObservationChecklist;
