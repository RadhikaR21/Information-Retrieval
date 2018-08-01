
import java.io.File;
import java.io.FileInputStream;
import java.io.PrintWriter;

import org.apache.tika.language.LanguageIdentifier;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;

public class big_file{
public static void main(String args[]) throws Exception
{
PrintWriter writer = new PrintWriter ("C:\\Users\\rradh\\Desktop\\CSCI572\\HW5\\HTMLFiles\\big.txt");
String dirPath = "C:\\Users\\rradh\\Desktop\\CSCI572\\HW5\\HTMLFiles";
File dir = new File(dirPath);
int count = 1;
try {
for(File file: dir.listFiles()){
	count++;
BodyContentHandler handler = new BodyContentHandler(-1);
    Metadata metadata = new Metadata();
    ParseContext pcontext = new ParseContext();
    HtmlParser htmlparser = new HtmlParser();
FileInputStream inputstream = new FileInputStream(file);
htmlparser.parse(inputstream, handler, metadata,pcontext);
String content = handler.toString();
String words[] = content.split(" ");
for(String t: words)
{
if(t.matches("[a-zA-Z]+\\.?"))
{
writer.print(t + " ");
}
}
}
} catch (Exception e) {
System.err.println("Caught IOException: " + e.getMessage());
e.printStackTrace();
}
writer.close();
}

}