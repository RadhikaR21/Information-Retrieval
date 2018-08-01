import java.io.IOException;
import java.util.StringTokenizer;
import java.util.*;

import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;

public class InvertedIndex
 {
  
  public static class TokenizerMapper extends Mapper<Object, Text, Text, Text>
  {

    private Text word = new Text();
    public void map(Object key, Text value, Context context) throws IOException, InterruptedException 
    {
      StringTokenizer itr = new StringTokenizer(value.toString());
      int count = 0;
      String one  = "";
     if(itr.hasMoreTokens())
        one =  itr.nextToken().toString();
      while (itr.hasMoreTokens())
      { 
         word.set(itr.nextToken());
        context.write(word, new Text(one));
      }
    } 
  }

  public static class IntSumReducer extends Reducer<Text,Text,Text,Text> 
  {
    private IntWritable result = new IntWritable();
	 public void reduce(Text key, Iterable<Text> values,Context context) throws IOException, InterruptedException
    {
      HashMap<String,Integer> map = new HashMap<>();    
      for (Text val : values)
       {
        String[] s = val.toString().split(":");
       if(s.length > 1 &&  map.containsKey(s[0]))
        {
        map.put(s[0],map.get(s[0])+Integer.parseInt(s[1].trim()));
        }
       else if(s.length == 1 && map.containsKey(s[0]))
        map.put(s[0],map.get(s[0])+1);
       else if(s.length > 1 && !map.containsKey(s[0]))
        map.put(s[0],Integer.parseInt(s[1].trim()));
        else
        map.put(s[0],1);
      }
	   StringBuilder sb = new StringBuilder("");
        for(Map.Entry<String,Integer> entry : map.entrySet())
        {
        sb.append(entry.getKey());
        sb.append(":");
        sb.append(entry.getValue());
        sb.append(" ");
        }
      context.write(key, new Text(sb.toString()));
    }
  }

  public static void main(String[] args) throws Exception {
    Configuration conf = new Configuration();
    Job job = new Job(conf, "wordcount");  
    job.setJarByClass(InvertedIndex.class);
    job.setMapperClass(TokenizerMapper.class);
    job.setCombinerClass(IntSumReducer.class);
    job.setReducerClass(IntSumReducer.class);
    job.setOutputKeyClass(Text.class);
    job.setOutputValueClass(Text.class);
    FileInputFormat.addInputPath(job, new Path(args[0]));
    FileOutputFormat.setOutputPath(job, new Path(args[1]));
    System.exit(job.waitForCompletion(true) ? 0 : 1);
  }
}

