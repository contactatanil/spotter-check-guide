
import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, XCircle, Clock, FileText, TrendingUp, Calendar } from 'lucide-react';

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

interface ProgressTrackerProps {
  checklist: ChecklistItem[];
}

const ProgressTracker: React.FC<ProgressTrackerProps> = ({ checklist }) => {
  const getStatusCounts = () => {
    return {
      satisfactory: checklist.filter(item => item.status === 'satisfactory').length,
      not_satisfactory: checklist.filter(item => item.status === 'not_satisfactory').length,
      in_progress: checklist.filter(item => item.status === 'in_progress').length,
      not_started: checklist.filter(item => item.status === 'not_started').length
    };
  };

  const getCategoryProgress = () => {
    const categories = [...new Set(checklist.map(item => item.category))];
    return categories.map(category => {
      const categoryItems = checklist.filter(item => item.category === category);
      const completed = categoryItems.filter(item => 
        item.status === 'satisfactory' || item.status === 'not_satisfactory'
      ).length;
      const satisfactory = categoryItems.filter(item => item.status === 'satisfactory').length;
      
      return {
        category,
        total: categoryItems.length,
        completed,
        satisfactory,
        progress: (completed / categoryItems.length) * 100,
        successRate: completed > 0 ? (satisfactory / completed) * 100 : 0
      };
    });
  };

  const getOverallProgress = () => {
    const completed = checklist.filter(item => 
      item.status === 'satisfactory' || item.status === 'not_satisfactory'
    ).length;
    return (completed / checklist.length) * 100;
  };

  const getCompletedItems = () => {
    return checklist.filter(item => 
      item.status === 'satisfactory' || item.status === 'not_satisfactory'
    ).sort((a, b) => {
      if (!a.dateCompleted || !b.dateCompleted) return 0;
      return b.dateCompleted.getTime() - a.dateCompleted.getTime();
    });
  };

  const statusCounts = getStatusCounts();
  const categoryProgress = getCategoryProgress();
  const overallProgress = getOverallProgress();
  const completedItems = getCompletedItems();

  return (
    <div className="space-y-6">
      <div className="mb-6">
        <h2 className="text-2xl font-bold mb-2">Progress Tracking</h2>
        <p className="text-gray-600">Detailed progress analysis and completion tracking.</p>
      </div>

      {/* Overall Progress */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <TrendingUp className="w-5 h-5" />
            Overall Progress
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div>
              <div className="flex justify-between items-center mb-2">
                <span className="text-sm font-medium">Completion Rate</span>
                <span className="text-2xl font-bold">{Math.round(overallProgress)}%</span>
              </div>
              <Progress value={overallProgress} className="h-4" />
              <p className="text-xs text-gray-500 mt-1">
                {checklist.filter(item => item.status === 'satisfactory' || item.status === 'not_satisfactory').length} of {checklist.length} items assessed
              </p>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="text-center p-3 bg-green-50 rounded-lg">
                <CheckCircle className="w-6 h-6 mx-auto mb-1 text-green-600" />
                <p className="text-xl font-bold text-green-600">{statusCounts.satisfactory}</p>
                <p className="text-xs text-gray-600">Satisfactory</p>
              </div>

              <div className="text-center p-3 bg-red-50 rounded-lg">
                <XCircle className="w-6 h-6 mx-auto mb-1 text-red-600" />
                <p className="text-xl font-bold text-red-600">{statusCounts.not_satisfactory}</p>
                <p className="text-xs text-gray-600">Not Satisfactory</p>
              </div>

              <div className="text-center p-3 bg-yellow-50 rounded-lg">
                <Clock className="w-6 h-6 mx-auto mb-1 text-yellow-600" />
                <p className="text-xl font-bold text-yellow-600">{statusCounts.in_progress}</p>
                <p className="text-xs text-gray-600">In Progress</p>
              </div>

              <div className="text-center p-3 bg-gray-50 rounded-lg">
                <FileText className="w-6 h-6 mx-auto mb-1 text-gray-600" />
                <p className="text-xl font-bold text-gray-600">{statusCounts.not_started}</p>
                <p className="text-xs text-gray-600">Not Started</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Category Progress */}
      <Card>
        <CardHeader>
          <CardTitle>Progress by Category</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {categoryProgress.map(category => (
              <div key={category.category} className="space-y-2">
                <div className="flex justify-between items-center">
                  <span className="font-medium">{category.category}</span>
                  <div className="flex items-center gap-2">
                    <span className="text-sm text-gray-600">
                      {category.completed}/{category.total}
                    </span>
                    <Badge variant="outline">
                      {Math.round(category.progress)}%
                    </Badge>
                  </div>
                </div>
                <Progress value={category.progress} className="h-2" />
                <div className="flex justify-between text-xs text-gray-500">
                  <span>{category.satisfactory} satisfactory</span>
                  <span>{Math.round(category.successRate)}% success rate</span>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Recent Completions */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Calendar className="w-5 h-5" />
            Recent Assessments
          </CardTitle>
        </CardHeader>
        <CardContent>
          {completedItems.length > 0 ? (
            <div className="space-y-3">
              {completedItems.slice(0, 5).map(item => (
                <div key={item.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    {item.status === 'satisfactory' ? (
                      <CheckCircle className="w-5 h-5 text-green-600" />
                    ) : (
                      <XCircle className="w-5 h-5 text-red-600" />
                    )}
                    <div>
                      <p className="font-medium text-sm">{item.title}</p>
                      <p className="text-xs text-gray-600">{item.category}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <Badge 
                      variant={item.status === 'satisfactory' ? 'default' : 'destructive'}
                      className="text-xs"
                    >
                      {item.status.replace('_', ' ').toUpperCase()}
                    </Badge>
                    {item.dateCompleted && (
                      <p className="text-xs text-gray-500 mt-1">
                        {item.dateCompleted.toLocaleDateString()}
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <Calendar className="w-12 h-12 mx-auto text-gray-400 mb-4" />
              <p className="text-gray-600">No assessments completed yet</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Performance Insights */}
      <Card>
        <CardHeader>
          <CardTitle>Performance Insights</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="p-4 bg-blue-50 rounded-lg">
                <h4 className="font-medium mb-2">Completion Velocity</h4>
                <p className="text-2xl font-bold text-blue-600">
                  {completedItems.length > 0 ? Math.round(completedItems.length / 7) : 0}
                </p>
                <p className="text-sm text-gray-600">items per week</p>
              </div>

              <div className="p-4 bg-purple-50 rounded-lg">
                <h4 className="font-medium mb-2">Success Rate</h4>
                <p className="text-2xl font-bold text-purple-600">
                  {completedItems.length > 0 
                    ? Math.round((statusCounts.satisfactory / completedItems.length) * 100)
                    : 0}%
                </p>
                <p className="text-sm text-gray-600">of assessed items</p>
              </div>
            </div>

            {statusCounts.not_satisfactory > 0 && (
              <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h4 className="font-medium text-yellow-800 mb-2">Areas for Improvement</h4>
                <p className="text-sm text-yellow-700">
                  {statusCounts.not_satisfactory} item(s) need attention. Review assessor feedback and resubmit evidence where needed.
                </p>
              </div>
            )}

            {statusCounts.in_progress > 0 && (
              <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 className="font-medium text-blue-800 mb-2">Pending Assessments</h4>
                <p className="text-sm text-blue-700">
                  {statusCounts.in_progress} item(s) are awaiting assessment. Your evidence has been submitted successfully.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ProgressTracker;
